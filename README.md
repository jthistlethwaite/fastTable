## FastTable
Class for generating server-rendered responsive HTML tables with sort, search, export/download, and paging functionality.

This project requires the jQuery Tablesorter 2.0 fork created by Mottie, which can be found here: https://mottie.github.io/tablesorter/docs/

### Example Usage:
```php
$ft = new FastTable\FastTable;
$ft->loadData(<mysql assoc query result>);
echo $ft->getTable();
echo $ft->generateJavaScript();
```

The above will generate a responsive HTML table with column sort, paging,
filter-by-column, and "download as spreadsheet" functionality.