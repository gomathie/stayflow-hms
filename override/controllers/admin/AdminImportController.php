<?php
/**
 * CSV Import takes Excel (.xlsx) files only. An uploaded .xlsx is converted to
 * a semicolon-separated CSV right after upload, in the same temporary file the
 * stock code already expects, which the stock import engine then reads
 * unchanged (it works on CSV internally). Anything that is not .xlsx is
 * refused. Old binary .xls is not read; save those as .xlsx first.
 */
require_once _PS_ROOT_DIR_.'/tools/simplexlsx/SimpleXLSX.php';

class AdminImportController extends AdminImportControllerCore
{
    public function ajaxProcessuploadCsv()
    {
        if (isset($_FILES['file']['name']) && empty($_FILES['file']['error'])) {
            if (!preg_match('/\.xlsx$/i', $_FILES['file']['name'])) {
                $_FILES['file']['error'] = Tools::displayError('Please upload an Excel (.xlsx) file.');
                die(json_encode($_FILES));
            }
            $this->convertXlsxUpload();
        }

        parent::ajaxProcessuploadCsv();
    }

    /**
     * Rewrites the uploaded file in place (same temp path, so the stock
     * move_uploaded_file() call that follows still recognises it) and
     * renames it to .csv so the stock extension check passes.
     */
    protected function convertXlsxUpload()
    {
        if (!empty($_FILES['file']['error']) || empty($_FILES['file']['tmp_name'])) {
            return;
        }

        $xlsx = \Shuchkin\SimpleXLSX::parse($_FILES['file']['tmp_name']);
        if (!$xlsx) {
            $_FILES['file']['error'] = Tools::displayError('Could not read this Excel file: ').\Shuchkin\SimpleXLSX::parseError();

            return;
        }

        $csv = '';
        foreach ($xlsx->rows() as $row) {
            $csv .= $this->xlsxRowToCsvLine($row).\PHP_EOL;
        }

        if (file_put_contents($_FILES['file']['tmp_name'], $csv) === false) {
            $_FILES['file']['error'] = $this->l('An error occurred while converting the Excel file.');

            return;
        }

        $_FILES['file']['name'] = preg_replace('/\.xlsx$/i', '.csv', $_FILES['file']['name']);
        $_FILES['file']['size'] = strlen($csv);
    }

    protected function xlsxRowToCsvLine(array $row)
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, $row, ';');
        rewind($stream);
        $line = stream_get_contents($stream);
        fclose($stream);

        return rtrim($line, "\r\n");
    }
}
