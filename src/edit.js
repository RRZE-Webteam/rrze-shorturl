const { useState } = wp.element;
const { TextControl, Button } = wp.components;
const { __ } = wp.i18n;

export default function Edit({ attributes, setAttributes }) {
    const [url, setUrl] = useState('');
    const [shortenedUrl, setShortenedUrl] = useState('');
    const [errorMessage, setErrorMessage] = useState('');

    const shortenUrl = () => {
        // Shorten the URL
        fetch('/wp-json/uniportal-short-url/v1/shorten', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                // 'X-WP-Nonce': uniportalShortUrl.nonce // Make sure to include a nonce for security
            },
            body: JSON.stringify({ url })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Response:', data); // Log the response data to the console
            if (!data.error) {
                // If URL is successfully shortened, set the shortened URL and clear error message
                setShortenedUrl(data.txt);
                setErrorMessage('');
            } else {
                // If there's an error, set error message
                setErrorMessage('Error: ' + data.txt);
                setShortenedUrl('');
            }
        })
        .catch(error => console.error('Error:', error));
    }

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
