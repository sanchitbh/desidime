<?php

@include_once('../vendor/autoload.php');

$merchants = ['flipkart', 'amazon', 'paytm'];

$filters = [
    ['title' => 'whiteline', 'merchant' => TRUE],
    ['title' => 'pampers'],
    ['title' => 'wet wipe'],
    ['title' => 'bread maker'],
    ['title' => 'koss'],
    ['title' => 'klipsh'],
    ['title' => 'fuzzy cooker'],
    ['title' => 'vacuum'],
    ['title' => 'wifi extender'],
    ['title' => 'bosch screwdriver'],
    ['title' => 'vero moda'],
    ['title' => 'forever new'],
    ['title' => 'gant'],
    ['title' => 'louis philippe'],
];

$driver = new \Behat\Mink\Driver\GoutteDriver();
$session = new \Behat\Mink\Session($driver);

$session->start();
$session->visit("https://www.desidime.com/forums/hot-deals-online?type=new");
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

    foreach ($filters as $filter) {
        $words = preg_split('/\s+/', $filter['title']);
        $pass = empty($filter['merchant']) || in_array(strtolower($merchant), $merchants);
        foreach ((array) $words as $word) {
            $pass = $pass && preg_match("/$word/i", $title);
        }

        if ($pass) {
            $results[] = "₹ $price: $title [$merchant]\n\nhttps://www.desidime.com/$link";
        }
    }
}

print_r($results);