const { useState } = wp.element;
const { TextControl, Button } = wp.components;
const { __ } = wp.i18n;

// Define the Edit component
const Edit = ({ attributes, setAttributes }) => {
    const [url, setUrl] = useState('');
    const [getparameter, setGetparameter] = useState('');
    const [shortenedUrl, setShortenedUrl] = useState('');
    const [selfExplanatoryUri, setSelfExplanatoryUri] = useState('');
    const [errorMessage, setErrorMessage] = useState('');
    const [qrCodeUrl, setQrCodeUrl] = useState('');

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
                body: JSON.stringify({ url, getparameter, uri: selfExplanatoryUri })
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
            <TextControl
                label={__('Self-Explanatory URI')}
                value={selfExplanatoryUri}
                onChange={setSelfExplanatoryUri}
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
