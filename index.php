<?php

// grab file from http://data.opendataportal.at/dataset/neos-finanztransparenz-bundespartei
// and convert to csv with comma separated and "-quote for escaping.
$fileHandle = fopen('data.csv', 'r');

// skip first line
fgets($fileHandle);

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);

$pdo->query('CREATE TABLE donations ( date DATE, name VARCHAR, nameCompare VARCHAR, amount INT, type VARCHAR, j VARCHAR);');

$insertStatement = $pdo->prepare('INSERT INTO donations (date, name, nameCompare, amount, type, j) VALUES ( :date, :name, :nameCompare, :amount, :type, :j );');
while (($line = fgets($fileHandle)) !== false) {
    $lineArray = str_getcsv($line, ',', '"');
    if ($lineArray[0] === '') {
        continue;
    }

    $filterList = [
        '-', '_', 'lif ', 'dr.', 'gmbh', 'di ', 'ddr.', 'zweckgebunden fuer online voting',
        'prof.', 'mmag.', 'mag.', 'dipl. ing.', 'dkfm.', 'spende', 'von', 'tupper party',
        'mag.iur.', 'herzblut-spende', 'fam.', '(fh)', 'ddolm.', 'di(fh)', 'fr.', ' msc',
        ' bsc', ' mba', 'gutschrift', 'pharm.', '6890 lustenau', 'holding', ',', '.'
    ];
    $firstnameFilter = [
        'hans', 'peter', 'markus', 'rainer', 'brigitte', 'rudolf', 'cord', 'ernst', 'margit',
        'verena', 'norbert', 'josef', 'christoph', 'alexander', 'hanns', 'knut', 'gerald',
        'catherine', 'd.', 'm.', 'anton', 'fritz', 'christian', 'karl', 'luise', 'franz'
    ];
    $nameCompare = strtolower($lineArray[1]);
    $nameCompare = str_replace($filterList, ' ', $nameCompare);
    $nameCompare = str_replace($filterList, ' ', $nameCompare); // double
    //$nameCompare = str_replace($firstnameFilter, ' ', $nameCompare);
    $nameCompare = trim($nameCompare);
    $nameCompare = str_replace('website', 'webseite', $nameCompare);

    $amount = floatval(str_replace(',', '', $lineArray[2]));
    $insertStatement->bindParam(':date', $lineArray[0]);
    $insertStatement->bindParam(':name', $lineArray[1]);
    $insertStatement->bindParam(':nameCompare', $nameCompare);
    $insertStatement->bindParam(':amount', $amount);
    $insertStatement->bindParam(':type', $lineArray[3]);
    $insertStatement->bindParam(':j', $lineArray[4]);
    $insertStatement->execute();
}
fclose($fileHandle);

echo '<h1>NEOS Spenden Data Analysis</h1>';

echo '<h2>Stats</h2>';
$statement = $pdo->query('SELECT SUM(amount) AS totalAmount FROM donations;');
$totalAmount = $statement->fetch()->totalAmount;
echo 'total amount: ' . $totalAmount;

echo '<h2>Amount by Type sorted by amount</h2>';
$statement = $pdo->query('SELECT type, SUM(amount) AS amount, (SUM(amount) * 100 / (SELECT SUM(amount) FROM donations)) AS percentage FROM donations GROUP BY type ORDER BY amount DESC;');
$amountByType = $statement->fetchAll();
echo '<table>';
echo '<tr><th>Type</th><th>Amount</th><th>Percentage of Total</th></tr>';
foreach ($amountByType as $obj) {
    echo '<tr>';
    echo '<td>' . $obj->type . '</td>';
    echo '<td style="text-align: right;">' . number_format((float) $obj->amount, 2, '.', '') . '</td>';
    echo '<td style="text-align: right;">' . number_format((float) $obj->percentage, 2, '.', '') . '%</td>';
    echo '</tr>';
}
echo '</table>';

echo '<h2>"Spende" Donations grouped by name sorted by Amount</h2>';
$statement = $pdo->query('SELECT name, nameCompare, SUM(amount) AS amount, type, (SUM(amount) * 100 / (SELECT SUM(amount) FROM donations WHERE type = "Spende")) AS percentage FROM donations WHERE type = "Spende" GROUP BY nameCompare ORDER BY amount DESC;');
$donations = $statement->fetchAll();
echo '<table>';
echo '<tr><th>Name</th><th>Name Compare</th><th>Type</th><th>Amount</th><th>Percentage of Total</th></tr>';
foreach ($donations as $donation) {
    echo '<tr>';
    echo '<td>' . $donation->name . '</td>';
    echo '<td>' . $donation->nameCompare . '</td>';
    echo '<td>' . $donation->type . '</td>';
    echo '<td style="text-align: right;">' . number_format((float) $donation->amount, 2, '.', '') . '</td>';
    echo '<td style="text-align: right;">' . number_format((float) $donation->percentage, 2, '.', '') . '%</td>';
    echo '</tr>';
}
echo '</table>';

echo '<h2>Donations grouped by name sorted by Amount</h2>';
$statement = $pdo->query('SELECT name, SUM(amount) AS amount, type, (SUM(amount) * 100 / (SELECT SUM(amount) FROM donations)) AS percentage FROM donations GROUP BY nameCompare ORDER BY amount DESC;');
$donations = $statement->fetchAll();
echo '<table>';
echo '<tr><th>Name</th><th>Type</th><th>Amount</th><th>Percentage of Total</th></tr>';
foreach ($donations as $donation) {
    echo '<tr>';
    echo '<td>' . $donation->name . '</td>';
    echo '<td>' . $donation->type . '</td>';
    echo '<td style="text-align: right;">' . number_format((float) $donation->amount, 2, '.', '') . '</td>';
    echo '<td style="text-align: right;">' . number_format((float) $donation->percentage, 2, '.', '') . '%</td>';
    echo '</tr>';
}
echo '</table>';

echo '<h2>Donations sorted by Date</h2>';
$statement = $pdo->query('SELECT *, (amount * 100 / (SELECT SUM(amount) FROM donations)) AS percentage FROM donations;');
$donations = $statement->fetchAll();
echo '<table>';
echo '<tr><th>Date</th><th>Name</th><th>Type</th><th>Amount</th><th>Percentage of Total</th></tr>';
foreach ($donations as $donation) {
    echo '<tr>';
    echo '<td>' . $donation->date . '</td>';
    echo '<td>' . $donation->name . '</td>';
    echo '<td>' . $donation->type . '</td>';
    echo '<td style="text-align: right;">' . number_format((float) $donation->amount, 2, '.', '') . '</td>';
    echo '<td style="text-align: right;">' . number_format((float) $donation->percentage, 2, '.', '') . '%</td>';
    echo '</tr>';
}
echo '</table>';


