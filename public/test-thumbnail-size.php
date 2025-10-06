<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Product Thumbnail Size</title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-body">
    <div style="padding: 20px;">
        <h2>🧪 Test Product Thumbnail Sizes</h2>
        
        <div style="background: white; padding: 20px; border-radius: 12px; margin: 20px 0;">
            <h3>Original Thumbnail (Should be 60px × 60px):</h3>
            <img src="../assets/uploads/products/product_1759737064_68e374e819c4c.jpeg" alt="iPhone 15 Pro" class="admin-product-thumb">
            
            <h3 style="margin-top: 30px;">Test in Table Structure:</h3>
            <table style="border-collapse: collapse; width: 100%;">
                <thead>
                    <tr style="background: #f5f5f7;">
                        <th style="padding: 12px; text-align: left;">Image</th>
                        <th style="padding: 12px; text-align: left;">Product Info</th>
                        <th style="padding: 12px; text-align: left;">Price</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid #d1d1d6;">
                        <td style="padding: 12px;">
                            <img src="../assets/uploads/products/product_1759737064_68e374e819c4c.jpeg" alt="iPhone 15 Pro" class="admin-product-thumb">
                        </td>
                        <td style="padding: 12px;">
                            <div class="admin-product-info">
                                <div class="admin-product-name">iPhone 15 Pro</div>
                                <div class="admin-product-desc">Latest iPhone with amazing features</div>
                            </div>
                        </td>
                        <td style="padding: 12px;">$999.00</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #d1d1d6;">
                        <td style="padding: 12px;">
                            <div class="admin-product-thumb admin-product-no-image">
                                <i class="fas fa-image"></i>
                            </div>
                        </td>
                        <td style="padding: 12px;">
                            <div class="admin-product-info">
                                <div class="admin-product-name">Product without Image</div>
                                <div class="admin-product-desc">This product has no image</div>
                            </div>
                        </td>
                        <td style="padding: 12px;">$299.00</td>
                    </tr>
                </tbody>
            </table>
            
            <h3 style="margin-top: 30px;">CSS Information:</h3>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 12px;">
                Current CSS Rules for .admin-product-thumb:<br>
                - width: 60px !important<br>
                - height: 60px !important<br>
                - object-fit: cover !important<br>
                - border-radius: 8px<br>
                - border: 1px solid #D1D1D6<br>
            </div>
            
            <h3 style="margin-top: 30px;">Browser Cache:</h3>
            <p>CSS loaded with cache buster: ?v=<?= time() ?></p>
            <button onclick="location.reload(true)" style="background: #007AFF; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer;">
                🔄 Force Refresh (Ctrl+F5)
            </button>
        </div>
        
        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <h3>🔧 Troubleshooting:</h3>
            <ol>
                <li><strong>Hard Refresh:</strong> Tekan Ctrl+F5 untuk clear cache</li>
                <li><strong>Check Developer Tools:</strong> F12 → Elements → Inspect image untuk lihat computed styles</li>
                <li><strong>CSS Priority:</strong> Style menggunakan !important untuk override</li>
                <li><strong>Multiple Selectors:</strong> CSS menggunakan berbagai selector untuk memastikan ter-apply</li>
            </ol>
        </div>
    </div>

    <script>
        // Log computed styles for debugging
        document.addEventListener('DOMContentLoaded', function() {
            const thumb = document.querySelector('.admin-product-thumb');
            if (thumb) {
                const styles = window.getComputedStyle(thumb);
                console.log('Computed styles for .admin-product-thumb:');
                console.log('Width:', styles.width);
                console.log('Height:', styles.height);
                console.log('Object-fit:', styles.objectFit);
                console.log('Border-radius:', styles.borderRadius);
            }
        });
    </script>
</body>
</html>