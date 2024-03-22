import { useState, useEffect } from '@wordpress/element';
import { PanelBody, DateTimePicker, TextControl, Button, FormTokenField } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import ClipboardJS from 'clipboard'; // Import ClipboardJS

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
    const [copied, setCopied] = useState(false);
    let clipboard; // Declare clipboard variable

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

        // Initialize clipboard instance
        clipboard = new ClipboardJS('.btn', {
            text: function () {
                return shortenedUrl;
            }
        });

        // Define success and error handlers
        clipboard.on('success', function (e) {
            setCopied(true);
            e.clearSelection();
        });

        clipboard.on('error', function (e) {
            console.error('Copy failed:', e.action);
        });

        // Clean up function to remove event listeners when component unmounts
        return () => {
            if (clipboard) {
                clipboard.destroy();
            }
        };

        // Fetch categories from shorturl_categories table
        fetch('/wp-json/short-url/v1/categories')
            .then(response => response.json())
            .then(data => {
                if (Array.isArray(data)) {
                    const categoriesOptions = data.map(term => ({
                        label: term.label,
                        value: term.id,
                        parent: term.parent_id || 0
                    }));
                    setCategoriesOptions(categoriesOptions);
                } else {
                    console.log('No categories found.');
                    setCategoriesOptions([]);
                }
            })
            .catch(error => {
                console.error('Error fetching shorturl_category terms:', error);
            });

        // Fetch tags from shorturl_tags table
        fetch('/wp-json/short-url/v1/tags')
            .then(response => response.json())
            .then(data => {
                console.log('ShortURL Tags Data:', data);
                if (Array.isArray(data)) {
                    const tagSuggestions = data.map(tag => ({ id: tag.id, value: tag.label }));
                    setTagSuggestions(tagSuggestions);
                } else {
                    console.log('No tags found.');
                    setTagSuggestions([]);
                }
            })
            .catch(error => {
                console.error('Error fetching shorturl_tags:', error);
            });

    }, [shortenedUrl]);

    const handleAddCategory = () => {
        const newCategoryLabel = prompt('Enter the label of the new category:');
        if (!newCategoryLabel) return;

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
                const updatedCategories = [...categoriesOptions, { label: newCategory.label, value: newCategory.id }];
                setCategoriesOptions(updatedCategories);
            })
            .catch(error => {
                console.error('Error adding category:', error);
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
            const allTagIds = selectedTags.map(tag => tag.id);

            const newTags = selectedTags.filter(tag => !tagSuggestions.some(suggestion => suggestion.value === tag.value));

            if (newTags.length > 0) {
                Promise.all(newTags.map(newTag => {
                    return fetch('/wp-json/short-url/v1/add-tag', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ label: newTag.value })
                    }).then(response => {
                        if (!response.ok) {
                            throw new Error('Failed to add tag');
                        }
                        return response.json();
                    }).then(newTag => newTag.id);
                })).then(newTagIds => {
                    const combinedTagIds = [...allTagIds, ...newTagIds];
                    continueShorteningUrl(combinedTagIds);
                }).catch(error => {
                    console.error('Error adding tag:', error);
                    setErrorMessage('Error: Failed to add tag');
                });
            } else {
                continueShorteningUrl(allTagIds);
            }
        }
    };

    const continueShorteningUrl = (tags) => {
        const shortenParams = {
            url: url.trim(),
            uri: selfExplanatoryUri,
            valid_until: validUntil,
            categories: selectedCategories,
            tags: tags
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

    const handleCopy = () => {
        console.log('handleCopy clicked');
        // Trigger copy action
        if (shortenedUrl) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(shortenedUrl)
                    .then(() => {
                        setCopied(true);
                    })
                    .catch(err => {
                        console.error('Copy failed:', err);
                    });
            } else {
                // Fallback method for browsers that do not support Clipboard API
                const textArea = document.createElement('textarea');
                textArea.value = shortenedUrl;
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    setCopied(true);
                } catch (err) {
                    console.error('Copy failed:', err);
                }
                document.body.removeChild(textArea);
            }
        }
    };

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
                        timeFormat={false} // Set to false to disable time selection
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
                        value={selectedTags.map(tag => tag.value)} // Extracting values from selectedTags
                        suggestions={tagSuggestions.map(tag => tag.value)} // Extracting values from tagSuggestions
                        onChange={(newTags) => {
                            const updatedTags = newTags.map(tagValue => ({
                                id: tagSuggestions.find(suggestion => suggestion.value === tagValue)?.id,
                                value: tagValue
                            }));
                            setSelectedTags(updatedTags);
                        }}
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

            {errorMessage && (
                <p style={{ color: 'red' }}>
                    {errorMessage}
                </p>
            )}

            {/* Display shortened URL and copy button */}
            {shortenedUrl && (
                <div>
                    <p>
                        {__('Shortened URL')}: {shortenedUrl}
                        &nbsp;&nbsp;<button class="btn" data-clipboard-target="#foo">
                            <img
                                src="data:image/svg+xml,%3Csvg height='1024' width='896' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M128 768h256v64H128v-64z m320-384H128v64h320v-64z m128 192V448L384 640l192 192V704h320V576H576z m-288-64H128v64h160v-64zM128 704h160v-64H128v64z m576 64h64v128c-1 18-7 33-19 45s-27 18-45 19H64c-35 0-64-29-64-64V192c0-35 29-64 64-64h192C256 57 313 0 384 0s128 57 128 128h192c35 0 64 29 64 64v320h-64V320H64v576h640V768zM128 256h512c0-35-29-64-64-64h-64c-35 0-64-29-64-64s-29-64-64-64-64 29-64 64-29 64-64 64h-64c-35 0-64 29-64 64z'/%3E%3C/svg%3E"
                                width="13"
                                alt="Copy to clipboard"
                                onClick={handleCopy} // Attach onClick event handler
                                style={{ cursor: 'pointer' }} // Add cursor style to indicate it's clickable
                            />
                        </button> {copied && <span>URL copied!</span>}
                    </p>
                    <img src={qrCodeUrl} alt="QR Code" />
                </div>
            )}
        </div>
    );
};

export default Edit;
