import { useState, useEffect } from '@wordpress/element';
import { FormTokenField, TextControl, Button, SelectControl, CheckboxControl, PanelBody } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

// Define the Edit component
const Edit = ({ attributes, setAttributes }) => {
    const [url, setUrl] = useState('');
    const [getparameter, setGetparameter] = useState('');
    const [shortenedUrl, setShortenedUrl] = useState('');
    const [selfExplanatoryUri, setSelfExplanatoryUri] = useState('');
    const [validUntil, setValidUntil] = useState('');
    const [selectedCategories, setSelectedCategories] = useState([]); // Change to array
    const [selectedTags, setSelectedTags] = useState([]);
    const [errorMessage, setErrorMessage] = useState('');
    const [qrCodeUrl, setQrCodeUrl] = useState('');
    const [categoriesOptions, setCategoriesOptions] = useState([]);
    const [tagsOptions, setTagsOptions] = useState([]);
    const [isLoading, setIsLoading] = useState(true); // Add loading state


    useEffect(() => {
        // Fetch categories from the shorturl_category taxonomy
        fetch('/wp-json/wp/v2/shorturl_category?fields=id,name,parent')
            .then(response => response.json())
            .then(data => {
                const categoriesOptions = data.map(term => ({
                    label: term.name,
                    value: term.id,
                    parent: term.parent || 0 // If parent is null, treat it as a top-level category
                }));

                // console.log('cats = ' + JSON.stringify(categoriesOptions));

                setCategoriesOptions(categoriesOptions);
            })
            .catch(error => {
                console.error('Error fetching shorturl_category terms:', error);
            });

        fetch('/wp-json/wp/v2/shorturl_tag?fields=id,name')
            .then(response => response.json())
            .then(data => {
                const tagsOptions = data.map(term => ({
                    label: term.name,
                    value: term.id.toString()
                }));

                setTagsOptions(tagsOptions)
            })
            .catch(error => {
                console.error('Error fetching shorturl_tag terms:', error);
            });
    }, []);



    // Define renderCategories function
    const renderCategories = (categories, level = 0, renderedCategories = new Set()) => {
        return categories.map((category, index) => {
            // Check if the category has already been rendered
            if (renderedCategories.has(category.value)) {
                return null; // Skip rendering this category
            }

            // Add the category ID to the set of rendered categories
            renderedCategories.add(category.value);

            const children = categoriesOptions.filter(child => child.parent === category.value);
            const isLastCategory = index === categories.length - 1;

            return (
                <div
                    key={category.value}
                    style={{
                        marginLeft: `${level * 20}px`,
                        marginBottom: `0`
                    }}
                >
                    <CheckboxControl
                        label={category.label}
                        checked={selectedCategories.includes(category.value)}
                        onChange={(isChecked) => {
                            if (isChecked) {
                                setSelectedCategories([...selectedCategories, category.value]);
                            } else {
                                setSelectedCategories(selectedCategories.filter(cat => cat !== category.value));
                            }
                        }}
                    />
                    {children.length > 0 && renderCategories(children, level + 1, renderedCategories)}
                </div>
            );
        });
    };


    const renderTags = () => {
        return (
            <FormTokenField
                label={__('Tags')}
                value={selectedTags}
                onChange={setSelectedTags}
                suggestions={tagsOptions?.map(tag => ({ label: tag.label, value: tag.value })) || []}
            />
        );
    };

    
    const shortenUrl = () => {
        let isValid = true;

        // Check if self-explanatory URI is not empty
        if (selfExplanatoryUri.trim() !== '') {
            // Remove spaces from the URI
            const uriWithoutSpaces = selfExplanatoryUri.replace(/\s/g, '');

            // Check if encodeURIComponent returns the same value for the URI
            if (encodeURIComponent(selfExplanatoryUri) !== encodeURIComponent(uriWithoutSpaces)) {
                setErrorMessage('Error: Self-Explanatory URI is not valid');
                isValid = false;
            }
        }

        if (isValid) {
            // Regular expression to check if the URL scheme is missing (neither http nor https)
            // Construct the URL with the correct scheme (prepend "https://" if needed)
            let formattedUrl = url.trim();
            if (!/^https?:\/\//i.test(formattedUrl)) {
                formattedUrl = "https://" + formattedUrl;
            }

            // Check if the URL already contains a query string
            const separator = formattedUrl.includes('?') ? '&' : '?';

            // Construct the final URL with the getparameter
            const finalUrl = getparameter ? `${formattedUrl}${separator}${getparameter}` : formattedUrl;

            const shortenParams = {
                url: finalUrl,
                uri: selfExplanatoryUri,
                valid_until: validUntil,
                category: selectedCategories.map(cat => parseInt(cat)),
                tags: selectedTags.map(tag => parseInt(tag))
            };

            // Proceed with URL shortening
            fetch('/wp-json/short-url/v1/shorten', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(shortenParams)
            })
                .then(response => response.json())
                .then(shortenData => {
                    console.log('My response:', shortenData);
                    if (!shortenData.error) {
                        setShortenedUrl(shortenData.txt);
                        setErrorMessage('');
                        generateQRCode(shortenData.txt); // Generate QR code after getting shortened URL
                    } else {
                        setErrorMessage('Error: ' + shortenData.txt);
                        setShortenedUrl('');
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    }

    const generateQRCode = (text) => {
        // Generate QR code using qrious library
        const qr = new QRious({
            element: document.getElementById('qrcode'),
            value: text,
            size: 150 // Adjust the size as per your requirement
        });
        setQrCodeUrl(qr.toDataURL()); // Set the QR code image URL
    }


    return (
        <div {...useBlockProps()}>
            <InspectorControls>
                <PanelBody title={__('URL Shortener Settings')}>
                    {/* TextControl, SelectControl for settings */}
                </PanelBody>
                <PanelBody title={__('Categories')}>
                    {categoriesOptions.length > 0 && renderCategories(categoriesOptions)}
                </PanelBody>
                <PanelBody title={__('Tags')}>
                {renderTags()}
                    {/* <SelectControl
                                label={__('Tags')}
                                multiple
                                value={selectedTags}
                                onChange={(tags) => setSelectedTags(tags)}
                                options={tagsOptions} // Provide options for the SelectControl
                            /> */}
                </PanelBody>
            </InspectorControls>

            <TextControl
                label={__('Enter URL')}
                value={url}
                onChange={setUrl}
            />

            <Button onClick={shortenUrl}>
                {__('Shorten URL')}
            </Button>

            {errorMessage && (
                <p style={{ color: 'red' }}>
                    {errorMessage}
                </p>
            )}

            {shortenedUrl && (
                <div>
                    <p>
                        {__('Shortened URL')}: {shortenedUrl}
                    </p>
                    <img src={qrCodeUrl} alt="QR Code" />
                </div>
            )}
        </div>
    );
};

export default Edit;
