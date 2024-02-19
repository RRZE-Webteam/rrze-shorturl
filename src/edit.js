const { useState } = wp.element;
const { TextControl, Button } = wp.components;
const { __ } = wp.i18n;

// Define the Edit component
const Edit = ({ attributes, setAttributes }) => {
    const [url, setUrl] = useState('');
    const [getparameter, setGetparameter] = useState('');
    const [shortenedUrl, setShortenedUrl] = useState('');
    const [errorMessage, setErrorMessage] = useState('');
    const [qrCodeUrl, setQrCodeUrl] = useState('');

    const shortenUrl = () => {
        fetch('/wp-json/short-url/v1/shorten', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ url, getparameter })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Response:', data);
            if (!data.error) {
                setShortenedUrl(data.txt);
                setErrorMessage('');
                generateQRCode(data.txt); // Generate QR code after getting shortened URL
            } else {
                setErrorMessage('Error: ' + data.txt);
                setShortenedUrl('');
            }
        })
        .catch(error => console.error('Error:', error));
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
            <TextControl
                label={__('Enter URL')}
                value={url}
                onChange={setUrl}
            />
            <TextControl
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
