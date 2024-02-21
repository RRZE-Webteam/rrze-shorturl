import { useState, useEffect } from '@wordpress/element';
import { TextControl, Button, SelectControl, PanelBody } from '@wordpress/components';
import { InspectorControls } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';

// Define the Edit component
const Edit = ({ attributes, setAttributes }) => {
    const [url, setUrl] = useState('');
    const [getparameter, setGetparameter] = useState('');
    const [shortenedUrl, setShortenedUrl] = useState('');
    const [selfExplanatoryUri, setSelfExplanatoryUri] = useState('');
    const [validUntil, setValidUntil] = useState('');
    const [selectedCategories, setSelectedCategories] = useState('');
    const [selectedTags, setSelectedTags] = useState([]);
    const [errorMessage, setErrorMessage] = useState('');
    const [qrCodeUrl, setQrCodeUrl] = useState('');
    const [categoriesOptions, setCategoriesOptions] = useState([]);
    const [tagsOptions, setTagsOptions] = useState([]);
    const [isLoading, setIsLoading] = useState(true); // Add loading state

    useEffect(() => {
        // Fetch categories from the shorturl_category taxonomy
        const categories = wp.data.select('core').getEntityRecords('taxonomy', 'shorturl_category');
        // Fetch tags from the shorturl_tag taxonomy
        const tags = wp.data.select('core').getEntityRecords('taxonomy', 'shorturl_tag');
    
        Promise.all([categories, tags]).then(([categories, tags]) => {
            // Check if categories or tags are null
            if (categories === null || tags === null) {
                // No categories or tags found, set options to empty arrays
                setCategoriesOptions([]);
                setTagsOptions([]);
                setIsLoading(false); // Set loading state to false
                return;
            }
    
            // Categories found, format them and set categoriesOptions
            const categoriesOptions = categories.map((category) => ({
                label: category.name,
                value: category.id.toString(),
            }));
            setCategoriesOptions(categoriesOptions);
    
            // Tags found, format them and set tagsOptions
            const tagsOptions = tags.map((tag) => ({
                label: tag.name,
                value: tag.id.toString(),
            }));
            setTagsOptions(tagsOptions);
    
            setIsLoading(false); // Set loading state to false
        });
    }, []);

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
            // Proceed with URL shortening
            fetch('/wp-json/short-url/v1/shorten', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    url, 
                    getparameter, 
                    uri: selfExplanatoryUri,
                    valid_until: validUntil, // Include valid_until date in the request body
                    categories: selectedCategories, // Include selected categories
                    tags: selectedTags // Include selected tags
                })
            })
            .then(response => response.json())
            .then(shortenData => {
                console.log('Response:', shortenData);
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
        <div>
            <InspectorControls>
                <PanelBody title={__('URL Shortener Settings')}>
                    <TextControl
                        label={__('GET Parameter')}
                        value={getparameter}
                        onChange={setGetparameter}
                    />
                    <TextControl
                        label={__('Self-Explanatory URI')}
                        value={selfExplanatoryUri}
                        onChange={setSelfExplanatoryUri}
                    />
                    <TextControl
                        label={__('Valid until')}
                        type="date"
                        value={validUntil}
                        onChange={setValidUntil}
                        min={new Date().toISOString().split('T')[0]} // Set minimum date to today
                        max={new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toISOString().split('T')[0]} // Set maximum date to one year from today
                    />
                    {isLoading ? (
                        <p>{__('Loading categories and tags...')}</p>
                    ) : (
                        <>
                            <SelectControl
                                label={__('Categories')}
                                value={selectedCategories}
                                onChange={(category) => setSelectedCategories(category)}
                                options={categoriesOptions} // Provide options for the SelectControl
                            />
                            <SelectControl
                                label={__('Tags')}
                                multiple
                                value={selectedTags}
                                onChange={(tags) => setSelectedTags(tags)}
                                options={tagsOptions} // Provide options for the SelectControl
                            />
                        </>
                    )}
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
