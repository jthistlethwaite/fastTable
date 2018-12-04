<?php
/**
 * @author Jason Thistlethwaite
 *
 * Class for generating server-rendered tables backed by jQuery tablesorter 2.0
 *  Credits:
 *      Christian Bach (original author of tablesorter)
 *      Rob Garrison (aka Mottie) (fork maintainer)
 *
 * @license MIT
 *
 *
 * Copyright 2017 Jason Thistlethwaite
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS
 * OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */
namespace FastTable;

/**
 * Class FastTable
 * @package FastTable
 *
 * Class for generating server-rendered tables with sort, search, paging, and export
 * functionality using associative arrays
 *
 *
 * Dependencies:
 *  - https://github.com/Mottie/tablesorter/
 *      - pager and output addons (copies included in examples/resources
 *
 *  - Bootstrap 3.3.x+
 *
 *
 * Example usage:
 *
 * $array = <mysql_assoc_result>
 *
 * $table = new FastTable();
 *
 * // Load mysql query results into the table
 * $table->loadData($array);
 *
 * // output the table HTML
 * echo $table->getTable();
 *
 * // output the required JavaScript
 * echo $table->generateJavascript();
 *
 *
 */
class FastTable
{

    /**
     * @var string Unique id for the table
     *
     * This needs to be unique so the pager, output, and other functions work properly when multiple tables are
     * in the same page.
     */
    public $tableId;

    /**
     * @var array Array of columns in the table
     */
    protected $columns = array();

    /**
     * @var array Array of associative arrays, each representing one row of the table
     */
    protected $rows = array();

    /**
     * @var array Array of columns NOT to display to the user
     *
     * Example:
     *
     * $rows = array(
     *  [ "id" => 5, "Name" => "bob" ],
     *  [ "id" => 6, "Name" => "john" ]
     * );
     *
     * $hiddenColumns = [ "id" ];
     *
     * Resulting table will not display the "id" column
     *
     */
    protected $hiddenColumns = array();

    /**
     * @var array Bootstrap popover configuration for columns
     *
     * This is checked when the <thead> is rendered. See generateHead and getColumnPopover
     */
    public $columnPopovers = array();

    /**
     * @var string Default placement for column popovers if not explicitly given
     */
    public $columnPopoverDefaultPlacement = 'top';

    /**
     * @var string Links, buttons, or text to display to the top-right of the table
     *
     */
    public $extraButtons = '';

    /**
     * @var string Classes applied to the resulting table
     */
    public $tableClasses = 'table';

    /**
     * @var string Link of javascript and CSS resources
     *
     * This is only here as a placeholder / helper to make it easy to get
     * up and running with FastTable
     *
     * Copies of these files can be found inside the examples/resources directory
     *
     */
    public $wwwResources = <<<EOQ
    <link href="resources/css/theme.bootstrap.css" rel="stylesheet" />
<script src="resources/js/jquery.tablesorter.min.js"></script>
<script src="resources/js/jquery.tablesorter.widgets.min.js"></script>
<link href="resources/css/jquery.tablesorter.pager.css" rel="stylesheet" />
<script src="resources/js/jquery.tablesorter.pager.min.js"></script>
<script src="resources/js/parser-input-select.min.js"></script>
<script src="resources/js/widget-output.min.js"></script>
EOQ;


    /**
     * @var string Theme to use for the table.
     *
     * Check out https://mottie.github.io/tablesorter/docs/themes.html
     */
    public $optionTheme = 'bootstrap';

    public $optionWidgets = array(
        "filter",
        "columns",
        "zebra",
        "pager",
        "output"
    );

    /**
     * @return array Array of columns
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return array The rows loaded by loadData()
     */
    public function getRows()
    {
        return $this->rows;
    }

    public function setColumns(array $columns)
    {
        $this->columns = $columns;
    }

    public function setRows(array $rows)
    {
        $this->rows = $rows;
    }

    /**
     * Append a row to the end of the table
     *
     * @param array $row Additional row
     */
    public function appendRow( array $row )
    {
        $this->rows[] = $row;
    }

    /**
     *  Populates the table with data
     *
     * @param array $array Associative array of table data
     *
     * Each row _should_ be an associative array with the keys as the name of the column
     *
     */
    public function loadArray(array $array)
    {
        $this->setColumns( array_keys($array[0]) );

        $this->setRows($array);
    }

    /**
     * generates bootstrap attributes for the column so hovering gives a popover
     *
     * This is how it works:
     *
     *  $this->columnPopovers is an associative array where each key is the name of a column that should have a popover
     *
     *  Each key is an array like this:
     *
     *  "User Id" => array(
     *      "title" => "<popover title>",
     *      "content" => "<popover contents>",
     *      "html" => "<true/false>",
     *      "placement" => "<popover placement>"
     *  )
     *
     * @param $column string Name of the column
     * @return string HTML attributes for the column header
     */
    private function getColumnPopover($column)
    {
        $meta = '';

        if (isset($this->columnPopovers[$column])) {

            $title = !empty($this->columnPopovers[$column]['title'])
                ? htmlspecialchars($this->columnPopovers[$column]['title'])
                : $column;

            $content = !empty($this->columnPopovers[$column]['content'])
                ? htmlspecialchars($this->columnPopovers[$column]['content'])
                : '';

            $html = isset($this->columnPopovers[$column]['html'])
                ? $this->columnPopovers[$column]['html'] : false;

            $placement = isset($this->columnPopovers[$column]['placement'])
                ? $this->columnPopovers[$column]['placement']
                : $this->columnPopoverDefaultPlacement;

            $meta = "data-toggle=\"popover\" data-placement=\"$placement\" title=\"$title\" data-content=\"$content\" "
                ."data-trigger=\"hover\" data-container=\"body\" data-html=\"$html\"";

        }

        return $meta;

    }

    /**
     * Creates all the <th> elements and applies popovers if configured
     *
     * @return string Contents of the <thead> table section.
     */
    private function generateHead()
    {
        $tableHead = '';

        foreach ($this->columns as $column) {

            if ( in_array($column, $this->hiddenColumns) === false) {

                $meta = $this->getColumnPopover($column);

                $tableHead .= "<th $meta>$column</th>";
            }
        }

        return $tableHead;
    }

    /**
     * @return string All the rows for the <tbody> section
     */
    private function generateRows()
    {
        $tableRows = '';

        foreach ($this->rows as $row)
        {
            $tableRow = '<tr>';

            foreach ($row as $columnName => $cell)
            {
                if (in_array($columnName, $this->hiddenColumns) === false) {
                    $tableRow .= '<td>'. $cell. '</td>';
                }
            }

            $tableRow .= '</tr>'. "\n";

            $tableRows .= $tableRow;
            unset($tableRow);
        }

        return $tableRows;
    }

    /**
     * @param string $tableId Optional tableId; generated automatically if none provided
     * @return string
     */
    public function getTable($tableId = 'viewTable')
    {
        /*
         * We want each table to have a unique id attribute
         */
        if ($tableId == 'viewTable' || $tableId == null) {
            $tableId = uniqid('viewTable');
        }

        $tableHead = $this->generateHead();

        $tableRows = $this->generateRows();


        $pager = '';

        $output = '';

        $extraButtons = '';

        if (!empty($this->extraButtons)) {
            $extraButtons .= $this->extraButtons;
        }

        if (in_array("output", $this->optionWidgets)) {
            $output = $this->generateOutputWidgetHTML();

            $extraButtons .= $output;
        }

        if (in_array("pager", $this->optionWidgets)) {
            $pager = $this->getPagerHtml();
        }

        $table =
        "<div class='FastTableContainer sortTable-$tableId'>".
        "<div class='btn-group pull-right'>$extraButtons</div>".
            "<table id='$tableId' class='{$this->tableClasses}'>\n".
            "<thead>$tableHead</thead>\n".
            $pager. "\n".
            "<tbody>$tableRows</tbody>\n</table>\n".
        "</div>";

        $this->tableId = $tableId;

        return $table;

    }

    public function getPagerHtml()
    {
        $cols = count($this->columns) - count($this->hiddenColumns);

        $pagerHtml = <<<EOT

<tfoot>
    <tr>
      <th colspan="$cols" class="ts-pager form-inline">
        <div class="btn-group btn-group-sm" role="group">
          <button type="button" class="btn btn-default first"><span class="glyphicon glyphicon-step-backward"></span></button>
          <button type="button" class="btn btn-default prev"><span class="glyphicon glyphicon-backward"></span></button>
        </div>
        <span class="pagedisplay"></span>
        <div class="btn-group btn-group-sm" role="group">
          <button type="button" class="btn btn-default next"><span class="glyphicon glyphicon-forward"></span></button>
          <button type="button" class="btn btn-default last"><span class="glyphicon glyphicon-step-forward"></span></button>
        </div>
        <select class="form-control input-sm pagesize" title="Select page size">
          <option value="5">5</option>
          <option selected="selected" value="10">10</option>
          <option value="20">20</option>
          <option value="30">30</option>
          <option value="all">All Rows</option>
        </select>
        <select class="form-control input-sm pagenum" title="Select page number"></select>
      </th>
    </tr>
</tfoot>
EOT;

        return $pagerHtml;
    }

    public function getPagerCode()
    {
        $pagerSize = 10;

        $tableId = $this->tableId;

        if (in_array("pager", $this->optionWidgets) === false) {
            return '';
        }

        $pagerCode = <<<EOT
       .tablesorterPager({

    size: $pagerSize,

    // target the pager markup - see the HTML block below
    container: $("#$tableId .ts-pager"),

    // target the pager page select dropdown - choose a page
    cssGoto  : ".pagenum",

    // remove rows from the table to speed up the sort of large tables.
    // setting this to false, only hides the non-visible rows; needed if you plan to add/remove rows with the pager enabled.
    removeRows: false,

    // output string - default is '{page}/{totalPages}';
    // possible variables: {page}, {totalPages}, {filteredPages}, {startRow}, {endRow}, {filteredRows} and {totalRows}
    output: '{startRow} - {endRow} / {filteredRows} ({totalRows})'

  })
EOT;
        return $pagerCode;

    }

    public function outputWidgetJavaScript()
    {
        $tableId = $this->tableId;

        $outputWidgetJavaScript = '';

        $outputWidgetJavaScript = <<<EOC
        var demos = [".sortTable-$tableId"];

        $.each(demos, function(groupIndex) {
            var \$this = $(demos[groupIndex]);

            \$this.find('.dropdown-toggle').click(function(e) {
                // this is needed because clicking inside the dropdown will close
                // the menu with only bootstrap controlling it.
                \$this.find('.dropdown-menu').toggle();
                return false;
            });
            // make separator & replace quotes buttons update the value
            \$this.find('.output-separator').click(function() {
                \$this.find('.output-separator').removeClass('active');
                var txt = $(this).addClass('active').html();
                \$this.find('.output-separator-input').val( txt );
                \$this.find('.output-filename').val(function(i, v) {
                    // change filename extension based on separator
                    var filetype = (txt === 'json' || txt === 'array') ? 'js' :
                        txt === ',' ? 'csv' : 'txt';
                    return v.replace(/\.\w+$/, '.' + filetype);
                });
                return false;
            });
            \$this.find('.output-quotes').click(function() {
                \$this.find('.output-quotes').removeClass('active');
                \$this.find('.output-replacequotes').val( $(this).addClass('active').text() );
                return false;
            });
            // header/footer toggle buttons
            \$this.find('.output-header, .output-footer').click(function() {
                $(this).toggleClass('active');
            });
            // clicking the download button; all you really need is to
            // trigger an "output" event on the table
            \$this.find('.download').click(function() {
                var typ,
                    \$table = \$this.find('table'),
                    wo = \$table[0].config.widgetOptions,
                    val = \$this.find('.output-filter-all :checked').attr('class');
                wo.output_saveRows     = val === 'output-filter' ? 'f' :
                    val === 'output-visible' ? 'v' :
                        // checked class name, see table.config.checkboxClass
                        val === 'output-selected' ? '.checked' :
                            val === 'output-sel-vis' ? '.checked:visible' :
                                'a';
                val = \$this.find('.output-download-popup :checked').attr('class');
                wo.output_delivery     = val === 'output-download' ? 'd' : 'p';
                wo.output_separator    = \$this.find('.output-separator-input').val();
                wo.output_replaceQuote = \$this.find('.output-replacequotes').val();
                wo.output_trimSpaces   = \$this.find('.output-trim').is(':checked');
                wo.output_includeHTML  = \$this.find('.output-html').is(':checked');
                wo.output_wrapQuotes   = \$this.find('.output-wrap').is(':checked');
                wo.output_saveFileName = \$this.find('.output-filename').val();

                // first example buttons, second has radio buttons
                if (groupIndex === 0) {
                    wo.output_includeHeader = \$this.find('button.output-header').is(".active");
                } else {
                    wo.output_includeHeader = !\$this.find('.output-no-header').is(':checked');
                    wo.output_headerRows = \$this.find('.output-headers').is(':checked');
                }
                // footer not included in second example
                wo.output_includeFooter = \$this.find('.output-footer').is(".active");

                \$table.trigger('outputTable');
                return false;
            });

            // add tooltip
            //\$this.find('.dropdown-menu [title]').tipsy({ gravity: 's' });

        });
EOC;


        return $outputWidgetJavaScript;
    }

    public function generateOutputWidgetHTML()
    {
        $outputWidgetHtml = '';


        $outputWidgetHtml = <<<EOS
    <button type="button" class="btn btn-default download"><i class="fa fa-download"></i> Export / Save</button>
    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
        <span class="caret"></span>
        <span class="sr-only">Toggle Dropdown</span>
    </button>
    <ul class="dropdown-menu save-menu" role="menu" style="min-width: 20em; padding: .25em">
        <li>
            <h5><span class="sectionTitle2">Output Options</span></h5>

        </li>
        <li>
            <label>Column Separator: <input class="output-separator-input" size="2" value="," type="text"></label>
             
            <p class="well well-sm">
                <button type="button" class="output-separator btn btn-default btn-xs active" title="comma">,</button>
                <button type="button" class="output-separator btn btn-default btn-xs" title="semi-colon">;</button>
                <button type="button" class="output-separator btn btn-default btn-xs" title="tab">Tab</button>
                <button type="button" class="output-separator btn btn-default btn-xs" title="space">Space</button>
                <button type="button" class="output-separator btn btn-default btn-xs" title="output JSON">json</button>
                <button type="button" class="output-separator btn btn-default btn-xs" title="output Array (see note)">array</button>             
            </p>

        </li>
        <li>
            <h5>Output Type</h5>
            <div class="btn-group output-download-popup" data-toggle="buttons" title="Download file or open in Popup window">
                <label class="btn btn-default btn-sm active">
                    <input name="delivery1" class="output-popup" checked="" type="radio"> Text Popup
                </label>
                <label class="btn btn-default btn-sm">
                    <input name="delivery1" class="output-download" type="radio"> Spreadsheet
                </label>
            </div>
        </li>
        <li>
            <label>Include:</label><br>
            <div class="btn-group output-filter-all" data-toggle="buttons" title="Output only filtered, visible, selected, selected+visible or all rows">
                <label class="btn btn-default btn-sm active">
                    <input name="getrows1" class="output-filter" checked="checked" type="radio"> This Page
                </label>
                <!--<label class="btn btn-default btn-sm">-->
                    <!--<input name="getrows1" class="output-visible" type="radio"> Visible-->
                <!--</label>-->
                <!--<label class="btn btn-default btn-sm">-->
                    <!--<input name="getrows1" class="output-selected" type="radio"> Selected-->
                <!--</label>-->
                <!--<label class="btn btn-default btn-sm">-->
                    <!--<input name="getrows1" class="output-sel-vis" type="radio"> Sel+Vis-->
                <!--</label>-->
                <label class="btn btn-default btn-sm">
                    <input name="getrows1" class="output-all" type="radio" onClick='$(".sortTable-{var:tableId} .pagesize").val("all").trigger("change");'> All Pages
                </label>
            </div>
        </li>
        <li>
            <button class="output-header btn btn-default btn-sm active" title="Include table header">Header</button>
            <button class="output-footer btn btn-default btn-sm" title="Include table footer">Footer</button>
        </li>
        <li class="divider"></li>
        <li>
            <label>Replace quotes: <input class="output-replacequotes" size="2" value="'" type="text"></label>
            <button type="button" class="output-quotes btn btn-default btn-xs active" title="single quote">'</button>
            <button type="button" class="output-quotes btn btn-default btn-xs" title="left double quote">“</button>
            <button type="button" class="output-quotes btn btn-default btn-xs" title="escaped quote">\"</button>
        </li>
        <li><label title="Remove extra white space from each cell">Trim spaces: <input class="output-trim" checked="" type="checkbox"></label></li>
        <li><label title="Include HTML from cells in output">Include HTML: <input class="output-html" type="checkbox"></label></li>
        <li><label title="Wrap all values in quotes">Wrap in Quotes: <input class="output-wrap" type="checkbox"></label></li>
        <li><label title="Choose a download filename">Filename: <input class="output-filename form-control" size="15" value="mytable.csv" type="text"></label></li>
    </ul>

EOS;


        return $outputWidgetHtml;


    }


    public function getOutputWidgetOptions()
    {
        $outputWidgetOptions = <<<EOC
                    output_separator     : ',',         // ',' 'json', 'array' or separator (e.g. ';')
                    // output_ignoreColumns : [0],         // columns to ignore [0, 1,... ] (zero-based index)
                    output_hiddenColumns : false,       // include hidden columns in the output
                    output_includeFooter : false,        // include footer rows in the output
                    output_includeHeader : true,        // include header rows in the output
                    output_headerRows    : false,       // output all header rows (if multiple rows)
                    output_dataAttrib    : 'data-name', // data-attribute containing alternate cell text
                    output_delivery      : 'p',         // (p)opup, (d)ownload
                    output_saveRows      : 'f',         // (a)ll, (v)isible, (f)iltered, jQuery filter selector (string only) or filter function
                    output_duplicateSpans: true,        // duplicate output data in tbody colspan/rowspan
                    output_replaceQuote  : '\u201c;',   // change quote to left double quote
                    output_includeHTML   : false,        // output includes all cell HTML (except the header cells)
                    output_trimSpaces    : true,       // remove extra white-space characters from beginning & end
                    output_wrapQuotes    : true,       // wrap every cell output in quotes
                    output_popupStyle    : 'width=580,height=310',
                    output_saveFileName  : 'mytable.csv',
                    // callback executed after the content of the table has been processed
                    output_formatContent : function(config, widgetOptions, data) {
                        // data.isHeader (boolean) = true if processing a header cell
                        // data.$cell = jQuery object of the cell currently being processed
                        // data.content = processed cell content (spaces trimmed, quotes added/replaced, etc)
                        // data.columnIndex = column in which the cell is contained
                        // data.parsed = cell content parsed by the associated column parser
                        return data.content;
                    },
                    // callback executed when processing completes
                    output_callback      : function(config, data, url) {
                        // return false to stop delivery & do something else with the data
                        // return true OR modified data (v2.25.1) to continue download/output
                        return true;
                    },
                    // callbackJSON used when outputting JSON & any header cells has a colspan - unique names required
                    output_callbackJSON  : function(\$cell, txt, cellIndex) {
                        return txt + '(' + cellIndex + ')';
                    },
                    // the need to modify this for Excel no longer exists
                    output_encoding      : 'data:application/octet-stream;charset=utf8,',
                    // override internal save file code and use an external plugin such as
                    // https://github.com/eligrey/FileSaver.js
                    output_savePlugin    : null /* function(config, widgetOptions, data) {
        var blob = new Blob([data], {type: widgetOptions.output_encoding});
        saveAs(blob, widgetOptions.output_saveFileName);
      } */
EOC;

        return $outputWidgetOptions;

    }

    /**
     * Generate the JavaScript needed to make the table responsive
     */
    public function generateJavascript()
    {
        $tableId = $this->tableId;

        $popovers = !empty($this->columnPopovers)
            ? "$(function () {  $('#$tableId [data-toggle=\"popover\"]').popover() });"
            : null;

        $widgets = !empty($this->optionWidgets)
            ? $this->optionWidgets
            : null;

        if ($widgets != null) {
            for ($i = 0; $i < count($widgets); $i++) {

                $widgets[$i] = '"'. $widgets[$i]. '"';
            }
        }

        $widgets = implode(",", $widgets);

        $pagerCode = $this->getPagerCode();

        $outputWidgetOptions = '';
        $outputWidgetJavaScript = '';

        if (in_array('output', $this->optionWidgets)) {
            $outputWidgetOptions = $this->getOutputWidgetOptions();
            $outputWidgetJavaScript = $this->outputWidgetJavaScript();
        }



        return <<<EOS
<script>
    $('#$tableId').tablesorter({
        theme: "{$this->optionTheme}",
        
        widgets: [ $widgets ],
        
        widgetOptions: {
            
            filter_reset : ".reset",
            filter_cssFilter: "form-control",
            
            $outputWidgetOptions
        }
    })$pagerCode;
    
    $outputWidgetJavaScript

    $popovers
    
    
</script>
EOS;


    }


}