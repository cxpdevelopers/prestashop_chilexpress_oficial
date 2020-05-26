<?php 

class PDFLabel extends PDFCore {

    public $filename;
    public $pdf_renderer;
    public $objects;
    public $template;
    public $send_bulk_flag = false;

    private $labelsHtml = '';


	/**
     * @param $objects
     * @param $template
     * @param $smarty
     * @param string $orientation
     */
    public function __construct($smarty, $orientation = 'P')
    {
        $this->pdf_renderer = new PDFGenerator((bool)Configuration::get('PS_PDF_USE_CACHE'), $orientation);
        $this->smarty = $smarty;

    }

    public function setFilename($filename) {
    	$this->filename = $filename;
    }
    public function setLabelsHTML($html) {
    	$this->labelsHtml = $html;
    }

    /**
     * Render PDF
     *
     * @param bool $display
     * @return mixed
     * @throws PrestaShopException
     */
    public function render($display = true)
    {
        $render = false;
        $this->pdf_renderer->setFontForLang(Context::getContext()->language->iso_code);

        //$this->pdf_renderer->createHeader('<h1>Header</h1>');
        //$this->pdf_renderer->createFooter('<h3>Footer</h3>');
        $this->pdf_renderer->createContent($this->labelsHtml);
        $this->pdf_renderer->writePage();
        $render = true;

    

        if ($render) {
            // clean the output buffer
            if (ob_get_level() && ob_get_length() > 0) {
                ob_clean();
            }
            return $this->pdf_renderer->render($this->filename, $display);
        }
    }



}