<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 12/4/18
 * Time: 9:54 AM
 */
require_once '../autoload.php';

use fastTable\FastTable;

include 'header_include.php';

$data = array(
  [
      "Animal" => "Dog",
      "Color" => "Brown"
  ],
    [
        "Animal" => "Cat",
        "Color" => "Black"
    ],
    [
        "Animal" => "Turtle",
        "Color" => "Green"
    ],
    [
        "Animal" => "Dog",
        "Color" => "unknown"
    ],
    [
        "Animal" => "Snake",
        "Color" => "Red"
    ],
    [
        "Animal" => "Dog",
        "Color" => "Brown"
    ],
    [
        "Animal" => "Cat",
        "Color" => "Black"
    ],
    [
        "Animal" => "Turtle",
        "Color" => "Green"
    ],
    [
        "Animal" => "Dog",
        "Color" => "unknown"
    ],
    [
        "Animal" => "Snake",
        "Color" => "Red"
    ],
    [
        "Animal" => "Dog",
        "Color" => "Brown"
    ],
    [
        "Animal" => "Cat",
        "Color" => "Black"
    ],
    [
        "Animal" => "Turtle",
        "Color" => "Green"
    ],
    [
        "Animal" => "Dog",
        "Color" => "unknown"
    ],
    [
        "Animal" => "Snake",
        "Color" => "Red"
    ]
);

$table = new FastTable();

$table->loadArray($data);

$table->columnPopovers = [
  "Animal" => [
      "title" => "Type of Animal",
      "content" => "This is the animal type"
  ]
];

$table->optionWidgets = [
    "filter",
    "columns",
    "zebra",
    "pager",
    "output"
];

echo $table->getTable();
echo $table->generateJavascript();

