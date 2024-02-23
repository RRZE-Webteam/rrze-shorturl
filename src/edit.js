import { useState, useEffect } from '@wordpress/element';
import { PanelBody, DateTimePicker, CheckboxControl, FormTokenField, TextControl, Button } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

const Edit = ({ attributes, setAttributes }) => {
    const { valid_until: defaultValidUntil } = attributes;

    const [url, setUrl] = useState('');
    const [getparameter, setGetparameter] = useState('');
    const [shortenedUrl, setShortenedUrl] = useState('');
    const [selfExplanatoryUri, setSelfExplanatoryUri] = useState('');
    const [validUntil, setValidUntil] = useState(defaultValidUntil);
    const [selectedCategories, setSelectedCategories] = useState([]);
    const [selectedTags, setSelectedTags] = useState([]);
    const [errorMessage, setErrorMessage] = useState('');
    const [qrCodeUrl, setQrCodeUrl] = useState('');
    const [categoriesOptions, setCategoriesOptions] = useState([]);
    const [tagsOptions, setTagsOptions] = useState([]);
    const [isLoading, setIsLoading] = useState(true);

    const onChangeValidUntil = newDate => {
        setValidUntil(newDate);
        setAttributes({ valid_until: newDate });
    };
    
    useEffect(() => {
        if (!validUntil) {
            const now = new Date();
            const nextYear = new Date(now.getFullYear() + 1, now.getMonth(), now.getDate());
            setValidUntil(nextYear);
            setAttributes({ valid_until: nextYear });
        }

        fetch('/wp-json/wp/v2/shorturl_category?fields=id,name,parent')
            .then(response => response.json())
            .then(data => {
                const categoriesOptions = data.map(term => ({
                    label: term.name,
                    value: term.id,
                    parent: term.parent || 0
                }));
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
                setTagsOptions(tagsOptions);
            })
            .catch(error => {
                console.error('Error fetching shorturl_tag terms:', error);
            });
    }, []);

    const renderCategories = (categories, level = 0, renderedCategories = new Set()) => {
        return categories.map((category, index) => {
            if (renderedCategories.has(category.value)) {
                return null;
            }

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

        if (selfExplanatoryUri.trim() !== '') {
            const uriWithoutSpaces = selfExplanatoryUri.replace(/\s/g, '');

            if (encodeURIComponent(selfExplanatoryUri) !== encodeURIComponent(uriWithoutSpaces)) {
                setErrorMessage('Error: Self-Explanatory URI is not valid');
                isValid = false;
            }
        }

        if (isValid) {
            let formattedUrl = url.trim();
            if (!/^https?:\/\//i.test(formattedUrl)) {
                formattedUrl = "https://" + formattedUrl;
            }

            const separator = formattedUrl.includes('?') ? '&' : '?';
            const finalUrl = getparameter ? `${formattedUrl}${separator}${getparameter}` : formattedUrl;

            const shortenParams = {
                url: finalUrl,
                uri: selfExplanatoryUri,
                valid_until: validUntil,
                category: selectedCategories.map(cat => parseInt(cat)),
                tags: selectedTags.map(tag => parseInt(tag))
            };

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
                        generateQRCode(shortenData.txt);
                    } else {
                        setErrorMessage('Error: ' + shortenData.txt);
                        setShortenedUrl('');
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    }

    const generateQRCode = (text) => {
        const qr = new QRious({
            element: document.getElementById('qrcode'),
            value: text,
            size: 150
        });
        setQrCodeUrl(qr.toDataURL());
    }

    return (
        <div {...useBlockProps()}>
            <InspectorControls>
                <PanelBody title={__('Self-Explanatory URI')}>
                <TextControl
                value={selfExplanatoryUri}
                onChange={setSelfExplanatoryUri}
            />
                </PanelBody>
                <PanelBody title={__('Validity')}>
                    <DateTimePicker
                        currentDate={validUntil}
                        onChange={onChangeValidUntil}
                        is12Hour={false} // Set to false to use 24-hour format
                        minDate={new Date()} // Minimum date is the current date
                        maxDate={new Date(new Date().getFullYear() + 1, new Date().getMonth(), new Date().getDate())} // Maximum date is one year from now
                        isInvalidDate={(date) => {
                            const nextYear = new Date(new Date().getFullYear() + 1, new Date().getMonth(), new Date().getDate());
                            return date > nextYear;
                        }}
                    />
                </PanelBody>
                <PanelBody title={__('Categories')}>
                    {categoriesOptions.length > 0 && renderCategories(categoriesOptions)}
                </PanelBody>
                {/* <PanelBody title={__('Tags')}>
                    {renderTags()}
                </PanelBody> */}
            </InspectorControls>

            <TextControl
                label={__('Enter URL')}
                value={url}
                onChange={setUrl}
            />
            <Button isPrimary onClick={shortenUrl}>
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
