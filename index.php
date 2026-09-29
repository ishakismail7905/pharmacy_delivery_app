<?php
require __DIR__ . '/db.php';
require __DIR__ . '/api_helpers.php';

$sql = "SELECT o.OrderID, c.CustomerName, c.Address,
        c.Latitude, c.Longitude, m.MedicineName,
        o.Quantity, o.OrderDate
        FROM Orders AS o
        INNER JOIN Customers AS c ON o.CustomerID = c.CustomerID
        INNER JOIN Medicines AS m ON o.MedicineID = m.MedicineID
        ORDER BY o.OrderID";

$orders = $conn->query($sql);
if (!$orders) {
    http_response_code(500);
    exit('Unable to retrieve order records.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pharmacy Delivery Monitoring</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<main>
    <h1>Pharmacy and Medicine Delivery Monitoring</h1>
    <p>Pharmacy origin: Kuala Lumpur</p>

    <table>
        <thead>
            <tr>
                <th>Customer Name</th>
                <th>Medicine Name</th>
                <th>Order Date</th>
                <th>Delivery Distance / Duration</th>
                <th>Order QR Code</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($orders->num_rows === 0): ?>
            <tr><td colspan="5">No orders found.</td></tr>
        <?php else: ?>
        <?php while ($row = $orders->fetch_assoc()): ?>
            <?php
            $delivery = getDeliveryInfo(
            (float) $row['Longitude'],
            (float) $row['Latitude'],
            $config['ors_api_key'],
            (float) $config['pharmacy_longitude'],
            (float) $config['pharmacy_latitude']
            );
            $qrData = 'Order ID: ' . $row['OrderID']
            . ' | Customer: ' . $row['CustomerName'];
            $qr = getQrCode($qrData);
            ?>
            <tr>
                <td><?= htmlspecialchars($row['CustomerName']) ?></td>
                <td><?= htmlspecialchars($row['MedicineName']) ?></td>
                <td><?= htmlspecialchars($row['OrderDate']) ?></td>
                <td>
                    <?php if ($delivery['ok']): ?>
                    <?= number_format($delivery['distance_km'], 2) ?> km /
                    <?= number_format($delivery['duration_min'], 1) ?> minutes
                    <?php else: ?>
                    <span class="error"><?= htmlspecialchars($delivery['message']) ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($qr['ok']): ?>
                    <img src="<?= htmlspecialchars($qr['data_uri']) ?>"
                    width="120" height="120" alt="QR code for order
                    <?= (int) $row['OrderID'] ?>">
                    <?php else: ?>
                    <span class="error"><?= htmlspecialchars($qr['message']) ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php endif; ?>
        </tbody>
    </table>
</main>
</body>
</html>
<?php $conn->close(); ?>
