const { useState } = wp.element;
const { TextControl, Button } = wp.components;
const { __ } = wp.i18n;

export default function Edit({ attributes, setAttributes }) {
    const [url, setUrl] = useState('');
    const [shortenedUrl, setShortenedUrl] = useState('');
    const [errorMessage, setErrorMessage] = useState('');

    const shortenUrl = () => {
        // Validate the URL
        fetch('/wp-json/uniportal-short-url/v1/validate-url', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': uniportalShortUrl.nonce // Make sure to include a nonce for security
            },
            body: JSON.stringify({ url })
        })
        .then(response => response.json())
        .then(data => {
            if (data.valid) {
                // If URL is valid, shorten it
                fetch('/wp-json/uniportal-short-url/v1/shorten-url', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': uniportalShortUrl.nonce // Make sure to include a nonce for security
                    },
                    body: JSON.stringify({ url })
                })
                .then(response => response.json())
                .then(data => {
                    setShortenedUrl(data.shortened_url);
                    setErrorMessage('');
                })
                .catch(error => console.error('Error:', error));
            } else {
                // If URL is invalid, set error message
                setErrorMessage(__('Invalid URL'));
                setShortenedUrl('');
            }
        })
        .catch(error => console.error('Error:', error));
    };

    return (
        <div>
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
                <p>
                    {__('Shortened URL')}: {shortenedUrl}
                </p>
            )}
        </div>
    );
};

