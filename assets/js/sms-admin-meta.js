/**
 * File: assets/js/sms-admin-meta.js
 * This script handles the QR code generation in the admin meta boxes.
 * It's loaded by class-sms-meta-boxes.php
 */
document.addEventListener('DOMContentLoaded', function() {
    
    function generateQR(containerId, qrValue) {
        var qrCodeContainer = document.getElementById(containerId);
        if (qrCodeContainer && qrValue) {
            qrCodeContainer.innerHTML = ''; // Clear previous QR
            new QRCode(qrCodeContainer, {
                text: qrValue,
                width: 128,
                height: 128,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
        }
    }

    function printQR(qrContainerId, qrValueId, titleSelector) {
        var qrContainer = document.getElementById(qrContainerId);
        var qrCodeValue = document.getElementById(qrValueId).value;
        var skateTitleEl = document.querySelector(titleSelector);
        var skateTitle = (skateTitleEl && skateTitleEl.value) ? skateTitleEl.value : 'Skate Item';
        
        var printWindow = window.open('', '_blank');
        printWindow.document.write('<html><head><title>Print QR Code</title>');
        printWindow.document.write('<style>body { font-family: sans-serif; text-align: center; } #qr-code-img { margin-top: 20px; } </style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write('<h2>' + skateTitle + '</h2>');
        
        var qrImg = qrContainer.querySelector('img');
        if(qrImg) {
            printWindow.document.write('<img id="qr-code-img" src="' + qrImg.src + '" style="width: 250px; height: 250px;">');
        }
        printWindow.document.write('<p>' + qrCodeValue + '</p>');
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        
        // Need a timeout for the image to render in the new window
        setTimeout(function() {
            printWindow.print();
            printWindow.close();
        }, 250);
    }

    // --- Skate Meta Box ---
    var skateQRContainer = document.getElementById('sms-qr-code-display');
    if (skateQRContainer) {
        generateQR('sms-qr-code-display', skateQRContainer.dataset.qrValue);

        document.getElementById('sms-print-qr').addEventListener('click', function() {
            printQR('sms-qr-code-display', 'skate_qr_code', '#titlewrap #title');
        });
    }

    // --- Skatemate Meta Box ---
    var skatemateQRContainer = document.getElementById('sms-qr-code-display-skatemate');
    if (skatemateQRContainer) {
        generateQR('sms-qr-code-display-skatemate', skatemateQRContainer.dataset.qrValue);

        document.getElementById('sms-print-qr-skatemate').addEventListener('click', function() {
            printQR('sms-qr-code-display-skatemate', 'skatemate_qr_code', '#titlewrap #title');
        });
    }
});

