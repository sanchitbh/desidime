<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
putenv('DEBUG=');

@include_once('../vendor/autoload.php');

$merchants = ['flipkart', 'amazon', 'paytm'];
$max_price = 10000;
$filters = [
    ['title' => 'pampers \bL\b'],
    ['title' => 'wet wipe'],
    ['title' => 'bread maker'],
    ['title' => 'koss'],
    ['title' => 'klipsh'],
    ['title' => 'fuzzy cooker'],
    ['title' => 'vacuum'],
    ['title' => 'wifi extender'],
    ['title' => 'bosch'],
    ['title' => 'fuzzy'],
    ['title' => 'veromoda'],
    ['title' => 'vero moda'],
    ['title' => 'forever new'],
    ['title' => 'gant'],
    ['title' => 'louis philippe'],
    ['title' => 'duracell'],
    ['title' => 'jockey short'],
    ['title' => 'uber pune'],
    ['title' => '\bift\b'],
    //['title' => '\bt\b shirt'],
    ['title' => 'ucb'],
    ['title' => 'flight ticket'],
    ['title' => 'amazonbasics'],
    ['title' => '\bband\b'],
    ['title' => 'mi band'],
    ['title' => 'drill'],
    ['title' => '\btool\b'],
    ['title' => '\bbig\b \bbasket\b'],
    ['title' => 'bigbasket'],
    ['title' => '\bnike\b'],
    ['title' => '\bzara\b'],
    ['title' => '\bVuitton\b'],
    ['title' => '\bAdidas\b'],
    ['title' => '\bGucci\b'],
    ['title' => '\bArmani\b'],
    ['title' => '\bgap\b'],
    ['title' => 'Under Armour'],
    ['title' => 'wills'],
    ['title' => 'marks spencer'],
    ['title' => 'crocs'],
    ['title' => 'bellies'],
];

$driver = new \Behat\Mink\Driver\GoutteDriver();
$session = new \Behat\Mink\Session($driver);

$session->start();
for ($i = 1; $i <= 3; $i++) {
    $session->visit("https://www.desidime.com/forums/hot-deals-online?type=new&page=$i");
    $page = $session->getPage();
    $boxes = $page->findAll('css', 'div.l-deal-box-textview');

    /** @var \Behat\Mink\Element\Element $box */
    foreach ($boxes as $box) {
        $link = $box->find('css', '.l-deal-dsp > a')->getAttribute('href');
        $title = $box->find('css', '.l-deal-dsp')->getText();
        $merchant = $box->find('css', '.l-deal-store')->getText();
        $price = 0;

        if ($priceBox = $box->find('css', '.l-deal-price')) {
            $price = $priceBox->getText();
        }

        if (TRUE) {
            foreach ($filters as $filter) {
                $words = preg_split('/ +/', $filter['title']);
                $pass = empty($filter['merchant']) || in_array(strtolower($merchant), $merchants);
                $pass = $pass && (empty($price) || empty($filter['price']) || ($price <= $filter['price']));

                foreach ((array) $words as $word) {
                    $pass = $pass && preg_match("/$word/i", $title);
                }

                if ($pass) {
                    $results[] = ['title' => $title, 'link' => "https://www.desidime.com/$link", 'price' => $price ?: 0, 'merchant' => $merchant, 'keyword' => $filter['title']];
                }
            }
        }
    }
}

if (!empty($results)) {
    $html = '';
    $seen = json_decode(cache_online('desidime'), TRUE) ?: [];

    if (empty($seen) || !is_array($seen)) $seen = [];

    foreach ($results as $result) {
        if (empty($seen[$result['link']])) {
            $html .= sprintf("<h3>%s - %s</h3>\n<a href=\"%s\">%s</a>\n\n", $result['title'] . (!empty($result['merchant']) ? "(" . $result['merchant'] . ")" : ''), $result['price'], $result['link'], $result['link']);
            $items[] = preg_replace('/' . preg_quote('\b') . '/', '', $result['keyword']);
            $seen[$result['link']] = TRUE;
        }
    }

    if (!empty($html)) {
        print $html;
        email('sanchit.notify@gmail.com', 'sanchitbh@gmail.com', 'desidime: ' . join(', ', $items), $html);

        if (!empty($seen)) {
            cache_online('desidime', json_encode($seen));
        }
    }
}