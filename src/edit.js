import { useState, useEffect } from '@wordpress/element';
import { PanelBody, DateTimePicker, TextControl, Button, FormTokenField } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

const Edit = ({ attributes, setAttributes }) => {
    const { valid_until: defaultValidUntil } = attributes;

    const [url, setUrl] = useState('');
    const [shortenedUrl, setShortenedUrl] = useState('');
    const [selfExplanatoryUri, setSelfExplanatoryUri] = useState('');
    const [validUntil, setValidUntil] = useState(defaultValidUntil);
    const [selectedCategories, setSelectedCategories] = useState([]);
    const [selectedTags, setSelectedTags] = useState([]);
    const [errorMessage, setErrorMessage] = useState('');
    const [qrCodeUrl, setQrCodeUrl] = useState('');
    const [categoriesOptions, setCategoriesOptions] = useState([]);
    const [tagSuggestions, setTagSuggestions] = useState([]);
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

        // Fetch categories from shorturl_categories table
        fetch('/wp-json/short-url/v1/categories')
            .then(response => response.json())
            .then(data => {
                console.log('ShortURL Categories Data:', data); // Log the data to see its structure
                if (Array.isArray(data)) {
                    const categoriesOptions = data.map(term => ({
                        label: term.label,
                        value: term.id,
                        parent: term.parent_id || 0
                    }));
                    setCategoriesOptions(categoriesOptions);
                } else {
                    console.log('No categories found.');
                    setCategoriesOptions([]); // Set categoriesOptions to an empty array if data is not an array
                }
            })
            .catch(error => {
                console.error('Error fetching shorturl_category terms:', error);
            });

        // Fetch tags from shorturl_tags table
        fetch('/wp-json/short-url/v1/tags')
            .then(response => response.json())
            .then(data => {
                console.log('ShortURL Tags Data:', data); // Log the data to see its structure
                if (Array.isArray(data)) {
                    const tagLabels = data.map(tag => tag.label); // Extract only the label strings
                    setTagSuggestions(tagLabels);
                } else {
                    console.log('No tags found.');
                    setTagSuggestions([]);
                }
            })
            .catch(error => {
                console.error('Error fetching shorturl_tags:', error);
            });
    }, []);


    const handleAddCategory = () => {
        const newCategoryLabel = prompt('Enter the label of the new category:');
        if (!newCategoryLabel) return; // If user cancels or enters an empty name, do nothing

        // Make a POST request to add the new category
        fetch('/wp-json/short-url/v1/add-category', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ label: newCategoryLabel })
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to add category');
                }
                return response.json();
            })
            .then(newCategory => {
                // Assuming newCategory has the structure { id: ..., name: ... }
                const updatedCategories = [...categoriesOptions, { label: newCategory.label, value: newCategory.id }];
                setCategoriesOptions(updatedCategories);
            })
            .catch(error => {
                console.error('Error adding category:', error);
                // Handle error, e.g., show error message to the user
            });
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
            // First, check if there are any new tags
            const newTags = selectedTags.filter(tag => !tagSuggestions.includes(tag));

            // If there are new tags, add them via REST API
            if (newTags.length > 0) {
                // Make a POST request to add the new tags
                Promise.all(newTags.map(newTag => {
                    return fetch('/wp-json/short-url/v1/add-tag', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ label: newTag })
                    }).then(response => {
                        if (!response.ok) {
                            throw new Error('Failed to add tag');
                        }
                        return response.json();
                    }).then(newTag => newTag.id);
                })).then(newTagIds => {
                    // Add the IDs of the new tags to the selectedTags array
                    const updatedSelectedTags = [...selectedTags, ...newTagIds];
                    setSelectedTags(updatedSelectedTags);

                    // Continue with the URL shortening process
                    continueShorteningUrl(updatedSelectedTags);
                }).catch(error => {
                    console.error('Error adding tag:', error);
                    setErrorMessage('Error adding tag');
                });
            } else {
                // No new tags, continue with the URL shortening process
                continueShorteningUrl(selectedTags);
            }
        }
    };

    const continueShorteningUrl = (tags) => {
        // Construct shortenParams object
        const shortenParams = {
            url: url.trim(),
            uri: selfExplanatoryUri,
            valid_until: validUntil,
            category: selectedCategories,
            tags: tags // Include selected tags
        };

        // Make a POST request to shorten the URL
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
    };

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
                        is12Hour={false}
                        minDate={new Date()}
                        maxDate={new Date(new Date().getFullYear() + 1, new Date().getMonth(), new Date().getDate())}
                        isInvalidDate={(date) => {
                            const nextYear = new Date(new Date().getFullYear() + 1, new Date().getMonth(), new Date().getDate());
                            return date > nextYear;
                        }}
                    />
                </PanelBody>
                <PanelBody title={__('Categories')}>
                    {categoriesOptions.map(category => (
                        <div key={category.value}>
                            <input
                                type="checkbox"
                                value={category.value}
                                checked={selectedCategories.includes(category.value)}
                                onChange={(event) => {
                                    const isChecked = event.target.checked;
                                    if (isChecked) {
                                        setSelectedCategories([...selectedCategories, category.value]);
                                    } else {
                                        setSelectedCategories(selectedCategories.filter(cat => cat !== category.value));
                                    }
                                }}
                            />
                            {' '}
                            <label>{category.label}</label>
                            <br />
                        </div>
                    ))}
                    <a href="#" onClick={handleAddCategory}>Add New Category</a>
                </PanelBody>
                <PanelBody title={__('Tags')}>
                    <FormTokenField
                        label="Tags"
                        value={selectedTags}
                        suggestions={tagSuggestions}
                        onChange={(newTags) => setSelectedTags(newTags)}
                    />
                </PanelBody>
            </InspectorControls>

            <TextControl
                label={__('Enter URL')}
                value={url}
                onChange={setUrl}
            />
            <Button isPrimary onClick={shortenUrl}>
                {__('Shorten URL')}
            </Button>

            {/* Display error message if exists */}
            {errorMessage && (
                <p style={{ color: 'red' }}>
                    {errorMessage}
                </p>
            )}

            {/* Display shortened URL and QR code if exists */}
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
