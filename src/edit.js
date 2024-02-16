const { useState } = wp.element;
const { TextControl, Button } = wp.components;
const { __ } = wp.i18n;




// Define the Edit component
const Edit = ({ attributes, setAttributes }) => {
    const [url, setUrl] = useState('');
    const [getparameter, setGetparameter] = useState(''); // New state for GET parameter
    const [shortenedUrl, setShortenedUrl] = useState('');
    const [qr, setQR] = useState('');
    const [errorMessage, setErrorMessage] = useState('');

    const shortenUrl = () => {
        // Shorten the URL
        fetch('/wp-json/short-url/v1/shorten', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                // 'X-WP-Nonce': shortUrl.nonce // Make sure to include a nonce for security
            },
            body: JSON.stringify({ url, getparameter }) // Include getParameter in the body
        })
        .then(response => response.json())
        .then(data => {
            console.log('Response:', data); // Log the response data to the console
            if (!data.error) {
                // If URL is successfully shortened, set the shortened URL and clear error message
                // var QRCode = require("qrcode-svg");
                // var svg = new QRCode("Hello World!").svg();
                // setQR(svg);
                
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
            <TextControl // New TextControl for GET parameter
                label={__('GET Parameter')}
                value={getparameter}
                onChange={setGetparameter}
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
            {qr && (
                <p>
                    {qr}
                </p>
            )}
        </div>
    );
};

export default Edit;
