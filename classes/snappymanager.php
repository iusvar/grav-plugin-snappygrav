<?php

namespace Grav\Plugin;
use Grav\Common\Grav;
use Grav\Common\Utils;
use RocketTheme\Toolbox\File\JsonFile;

class SnappyManager
{
    //public $grav;
    public $json_response;
    protected $post;
    protected $task;

    //protected $lang;

    public function __construct(Grav $grav)
    {
        //$this->grav     = $grav;
        //$this->config   = $this->grav['config'];
        //$this->lang = $this->grav['language'];
    }


    public function setMessage($msg, $type = 'info')
    {
      $grav = Grav::instance();
      $messages = $grav['messages'];

      $messages->add($msg, $type);
    }


    public function messages($type = null)
    {
      $grav = Grav::instance();
      $messages = $grav['messages'];

      return $messages->fetch($type);
    }


    public function execute($task, $post)
    {
        $this->task = $task;
        $this->post = $post;
        if (!$this->validateNonce()) {
            return false;
        }

        $method = 'task' . ucfirst($this->task);

        if (method_exists($this, $method)) {
            try {
                $success = call_user_func([$this, $method]);
            } catch (\RuntimeException $e) {
                $success = true;
                $this->setMessage($e->getMessage(), 'error');
            }
        }
        return $success;
    }


    protected function validateNonce()
    {
      $grav = Grav::instance();
      $lang = $grav['language'];
      $uri = $grav['uri'];
      
      if (method_exists('Grav\Common\Utils', 'getNonce')) {
        if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
          if (isset($this->post['snappy-nonce'])) {
            $nonce = $this->post['snappy-nonce'];
          } else {
            $nonce = $uri->param('snappy-nonce');
          }

            if (!$nonce || !Utils::verifyNonce($nonce, 'snappy-form')) {
              if ($this->task == 'addmedia') {

                $message = sprintf($lang->translate('PLUGIN_ADMIN.FILE_TOO_LARGE', null),
                  ini_get('post_max_size'));

                $this->json_response = [
                  'status'  => 'error',
                  'message' => $message
                ];

                return false;
              }

              $this->setMessage($lang->translate('PLUGIN_ADMIN.INVALID_SECURITY_TOKEN'), 'error');
              $this->json_response = [
                'status'  => 'error',
                'title' => $lang->translate('PLUGIN_SNAPPYGRAV.INVALID_SECURITY_TOKEN_TITLE'),
                'message' => $lang->translate('PLUGIN_SNAPPYGRAV.INVALID_SECURITY_TOKEN_TEXT')
              ];

              return false;
            }
            unset($this->post['snappy-nonce']);
        } else {
          $nonce = $uri->param('snappy-nonce');
          if (!isset($nonce) || !Utils::verifyNonce($nonce, 'snappy-form')) {
            $this->setMessage($lang->translate('PLUGIN_SNAPPYGRAV.INVALID_SECURITY_TOKEN'), 'error');
            $this->json_response = [
              'status'  => 'error',
              'title' => $lang->translate('PLUGIN_SNAPPYGRAV.INVALID_SECURITY_TOKEN_TITLE'),
              'message' => $lang->translate('PLUGIN_SNAPPYGRAV.INVALID_SECURITY_TOKEN_TEXT')
            ];
            return false;
          }
        }
      }
      return true;
    }


    protected function taskSnappy()
    {
      $grav = Grav::instance();
      $config = $grav['config'];
      $lang = $grav['language'];
      $locator = $grav['locator'];
      $uri = $grav['uri'];

      $export_branch  = $uri->param('branch');
      $export_route   = $uri->param('route');
      $export_route   = str_replace('@','/',$export_route);

      try {
        // selected library by the administrator
        $library = $config->get('plugins.snappygrav.library');

        // loading composer.json
        $path = $locator->findResource('plugin://snappygrav/composer.json', true, true);
        $string = file_get_contents($path);

        $space = '&nbsp;&nbsp;&nbsp;';
        $command[$library] = '';
        $knp_snappy = true;
        $command['knp-snappy'] = '';
        $command['note'] = '';

        // check if the library is present in the composer.json
        $library_been_found = strpos($string, $library);
        if ($library_been_found === false) {
          $command['mpdf'] = $space.'composer require mpdf/mpdf';
          $command['tcpdf'] = $space.'composer require tecnickcom/tcpdf';
          $command['wkhtmltopdf'] = $space.'composer require h4cc/wkhtmltopdf-i386 (*)';
        }

        // check if the knplabs/knp-snappy library is present in the composer.json
        if($library=='wkhtmltopdf'){
          $knp_snappy=false;
          $library_been_found = false;
          $command['wkhtmltopdf'] = $space.'composer require h4cc/wkhtmltopdf-i386 (*)';
          $command['note'] = '<br/>(*) '.$lang->translate('PLUGIN_SNAPPYGRAV.APPROPRIATE_LIBRARY');
          $newline = '<br/>';
          $wk_position = $config->get('plugins.snappygrav.wk_position');
          $wk_path = $config->get('plugins.snappygrav.wk_path');
          if($wk_position=='os'){
            if(file_exists($wk_path)){
              $library_been_found = true;
              $command['wkhtmltopdf'] = '';
              $command['note'] = '';
              $newline = '';
            }
          }
          $knp_snappy = strpos($string, 'knp-snappy');
          if ($knp_snappy===false){
            $command['knp-snappy'] = $newline.$space.'composer require knplabs/knp-snappy';
          }
        }

        // returns the message containing the library to be installed
        if ($library_been_found === false || $knp_snappy === false) {
          $message = $lang->translate('PLUGIN_SNAPPYGRAV.MOVE_AND_RUN');
          $this->json_response = [
            'status'    => 'error',
            'title'     => strtoupper($library).' '.$lang->translate('PLUGIN_SNAPPYGRAV.NOT_INSTALLED'),
            'message' => str_replace('%1', '<b>user/plugins/snappygrav/</b>', $message) . '<br/><code>'.$command[$library].$command['knp-snappy'].'</code>'.$command['note']
          ];
          return true;
        }

        // collect page data to request production of the document
        $return_value = $this->makeDocument($export_route, $export_branch);
        $encoded_pdf = $return_value['encoded_pdf'];
        $filename = $return_value['filename'];
        
      } catch (\Exception $e) {
        $this->json_response = [
          'status'    => 'error',
          'title'     => $lang->translate('PLUGIN_SNAPPYGRAV.AN_ERROR_OCCURRED').' '.$e->getLine(),
          'message'   => $e->getMessage()
        ];
        return true;
      }

      $attachment_button  = $lang->translate('PLUGIN_SNAPPYGRAV.ATTACHMENT');
      $message            = $lang->translate('PLUGIN_SNAPPYGRAV.YOUR_DOCUMENT_IS_READY_FOR');
      //$message            = str_replace('%1', strtoupper($export_type), $message);

      // returns the name and content of the created document
      $this->json_response = [
        'status'        => 'success',
        'message'       => $message,
        'encoded_pdf'   => $encoded_pdf,
        'filename'      => $filename
      ];

      return true;
    }


    protected function makeDocument($route, $branch)
    {
      $grav = Grav::instance();
      $config = $grav['config'];
      $page = $grav['page'];
      $twig = $grav['twig'];
      $themes = $grav['themes'];

      $parameters = [];
      $html = [];
      $temp_html = '';
      $filename = 'completepdf';
      
      $metadata['author']   = $config->get('site.author.name', $_SERVER['SERVER_NAME']);
      $metadata['title']    = 'Title';
      $metadata['subject']  = $config->get('site.metadata.description', $_SERVER['SERVER_NAME']);
      $metadata['keywords'] = 'Keywords';
      $metadata['creator']  = $config->get('site.title', $_SERVER['SERVER_NAME']);
      
      // complete
      if( empty($route) ) {
        $temp_html = $twig->processTemplate('complete.html.twig', ['page' => $page]);
        
      // single or branch
      } else {
        $found = $page->find( $route );
        $parameters['branch'] = ($branch == 'yes' ? true: false);
        $parameters['breadcrumbs']  = $this->getCrumbs( $found );
        $filename = $found->title();
        $temp_html = $twig->processTemplate('single.html.twig', ['page' => $found, 'parameters' => $parameters]);

        $metadata['author'] = ( isset($found->taxonomy()['author']) ? implode(',', $found->taxonomy()['author']) : $metadata['author'] );
        $metadata['title'] = $found->title();
        $keywords = ( isset($found->taxonomy()['tag']) ? implode(',', $found->taxonomy()['tag']) : 'Please, set taxonomy' );
        $metadata['keywords'] = $keywords;
      }
      $html[] = preg_replace('/<iframe>.*<\/iframe>/is', '', $temp_html);

      // create the document with the selected library
      $pdf = $this->servePDF( $html, $metadata );
      $encoded_pdf = base64_encode( $pdf );

      $return_value = array();
      $return_value['encoded_pdf'] = $encoded_pdf;
      $return_value['filename'] = $filename;

      return $return_value;
    }


    protected function getCrumbs( $page )
    {
      $grav = Grav::instance();
      $pages = $grav['pages'];
      
      $current = $page;
      $hierarchy = array();
      while ($current && !$current->root()) {
        $hierarchy[$current->url()] = $current;
        $current = $current->parent();
      }
      $home = $pages->dispatch('/');
      if ($home && !array_key_exists($home->url(), $hierarchy)) {
        $hierarchy[] = $home;
      }
      $elements = array_reverse($hierarchy);
      $crumbs = array();
      foreach ($elements as $key => $crumb) {
        $crumbs[] = [ 'route' => $crumb->route(), 'title' => $crumb->title() ];
      }

      return $crumbs;
    }


    public function getOption()
    {
      $grav = Grav::instance();
      $config = $grav['config'];
      $lang = $grav['language'];
      
      $pdf_option = [];
      $pdf_option['bottom'] = $config->get('plugins.snappygrav.margin_bottom') ?: 10;
      $pdf_option['left'] = $config->get('plugins.snappygrav.margin_left') ?: 10;
      $pdf_option['right'] = $config->get('plugins.snappygrav.margin_right') ?: 10;
      $pdf_option['top'] = $config->get('plugins.snappygrav.margin_top') ?: 10;
      $pdf_option['page_size'] = $config->get('plugins.snappygrav.page_size') ?: 'A4';
      $pdf_option['encoding'] = $config->get('plugins.snappygrav.page_size') ?: 'utf-8';
      $pdf_option['orientation'] = $config->get('plugins.snappygrav.orientation') ?: 'P';
      $pdf_option['showwatermarktext'] = $config->get('plugins.snappygrav.showwatermarktext') ?: false;
      $pdf_option['setwatermarktext'] = $config->get('plugins.snappygrav.setwatermarktext') ?: $lang->translate('PLUGIN_SNAPPYGRAV.DRAFT');
      $pdf_option['watermarktextalpha'] = $config->get('plugins.snappygrav.watermarktextalpha') ?: 0.2;

      return $pdf_option;
    }
    

    public function servePDF( $html, $metadata )
    {
      $grav = Grav::instance();
      $config = $grav['config'];

      $library = $config->get('plugins.snappygrav.library') ?: 'mpdf';
      switch ( $library )
      {
        case 'mpdf':
          $pdf = static::engineMPDF( $html, $metadata );
          break;
        case 'tcpdf':
          $pdf = static::engineTCPDF( $html, $metadata );
          break;
        case 'wkhtmltopdf':
          $pdf = static::engineWKHTMLTOPDF( $html, $metadata );
          break;
      }
      return $pdf;
    }
    

    public function engineMPDF( $html, $metadata )
    {
      $pdf_option = static::getOption();
      
      $encoding = $pdf_option['encoding'];
      $format = $pdf_option['page_size'];
      $orientation = substr(strtoupper($pdf_option['orientation']),0,1);
      
      $mpdf = new \Mpdf\Mpdf(['mode' => $encoding, 'format' => $format,'orientation' => $orientation]);
      
      $mpdf->SetMargins( $pdf_option['left'],$pdf_option['right'],$pdf_option['top'],$pdf_option['bottom'] );

      if( $pdf_option['showwatermarktext'] ){
        $mpdf->showWatermarkText = true;
        $mpdf->SetWatermarkText( $pdf_option['setwatermarktext'] );
        $mpdf->watermarkTextAlpha = $pdf_option['watermarktextalpha'];
      }
      
      if(!empty($metadata)){
        $mpdf->SetTitle( isset($metadata['title']) ? $metadata['title'] : 'set title' );
        $mpdf->SetCreator( isset($metadata['creator']) ? $metadata['creator'] : 'set creator' );
        $mpdf->SetAuthor( isset($metadata['author']) ? $metadata['author'] : 'set author' );
        $mpdf->SetSubject( isset($metadata['subject']) ? $metadata['subject'] : 'set subject' );
        $mpdf->SetKeywords( isset($metadata['keywords']) ? $metadata['keywords']: 'set keywords' );
      }

      $output = implode('',$html);
      $mpdf->WriteHTML($output);
      $pdf = $mpdf->Output(null,'S'); // [I]nline, [D]ownload, [F]ile, [S]tring_return
      
      return $pdf;
    }


    public function engineTCPDF( $html, $metadata )
    {
      $grav = Grav::instance();
      $config = $grav['config'];
      
      $pdf_option = static::getOption();
      $orientation = substr(strtoupper($pdf_option['orientation']),0,1);
/*
      define ('K_PATH_IMAGES', dirname(__FILE__).'/../images/');
      define ('PDF_HEADER_LOGO', 'logo.png');
      define ('PDF_HEADER_STRING', "by IUSVAR\nhttp://iusvar.alwaysdata.net/grav/");
      define ('PDF_UNIT', 'mm');
      define ('PDF_FONT_NAME_DATA', 'helvetica');
      define ('PDF_FONT_SIZE_DATA', 8);
      define ('PDF_FONT_MONOSPACED', 'courier');
      define ('PDF_MARGIN_HEADER', 5);
      define ('PDF_MARGIN_FOOTER', 10);
      define ('PDF_IMAGE_SCALE_RATIO', 1.25);
      if (!empty('PDF_HEADER_LOGO')) {
        define ('PDF_HEADER_LOGO_WIDTH', 50);
      } else {
        define ('PDF_HEADER_LOGO_WIDTH', 0);
      }
*/
      $tcpdf_setprintheader = ( $config->get('plugins.snappygrav.tcpdf_setprintheader') ? true : false );
      $tcpdf_setprintfooter = ( $config->get('plugins.snappygrav.tcpdf_setprintfooter') ? true : false );
      $tcpdf = new \TCPDF($orientation, PDF_UNIT, $pdf_option['page_size'], true, $pdf_option['encoding'], false);
      $tcpdf->SetMargins( $pdf_option['left'],$pdf_option['top'],$pdf_option['right'],$pdf_option['bottom'] );
      $tcpdf->SetPrintHeader( $tcpdf_setprintheader );
      $tcpdf->SetPrintFooter( $tcpdf_setprintfooter );

      if(!empty($metadata)){
        $tcpdf->SetTitle( isset($metadata['title']) ? $metadata['title'] : 'title' );
        $tcpdf->SetCreator( isset($metadata['creator']) ? $metadata['creator'] : 'creator' );
        $tcpdf->SetAuthor( isset($metadata['author']) ? $metadata['author'] : 'author' );
        $tcpdf->SetSubject( isset($metadata['subject']) ? $metadata['subject'] : 'subject' );
        $tcpdf->SetKeywords( isset($metadata['keywords']) ? $metadata['keywords'] : 'keywords' );
      }
/*
      $tcpdf->setJPEGQuality(75);
      $tcpdf->setFooterData(array(0,64,0), array(0,64,128));
      $tcpdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
      $tcpdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
      $tcpdf->SetHeaderMargin(PDF_MARGIN_HEADER);
      $tcpdf->SetFooterMargin(PDF_MARGIN_FOOTER);
      $tcpdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
      $tcpdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
      $tcpdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, $metadata['title'], PDF_HEADER_STRING, array(0, 64, 255), array(0, 64, 128));
      if (@file_exists(dirname(__FILE__).'/lang/it.php')) {
        require_once(dirname(__FILE__).'/lang/it.php');
        $tcpdf->setLanguageArray($l);
      }
      $tcpdf->setFontSubsetting(false);
      $tcpdf->SetFont('times', '', '10', '', true);
*/
      $tcpdf->AddPage();
      $output = implode('',$html);
      $tcpdf->writeHTML($output, true, false, true, false, '');

      $pdf = $tcpdf->Output('', 'S'); //[I]nline, [D]ownload, [F]ile, [S]tring, [FI], [FD], [E]ncoding base64 
      
      return $pdf;
    }


    public function engineWKHTMLTOPDF( $html, $metadata )
    {
      $grav = Grav::instance();
      $config = $grav['config'];
      
      $pdf_option = static::getOption();
      
      // Placement/Path of the wkhtmltopdf program
      $wk_position = $config->get('plugins.snappygrav.wk_position');
      $wk_path = $config->get('plugins.snappygrav.wk_path');

      switch ($wk_position) {
        case 'data':
          $wk_path = ( empty($wk_path) ? GRAV_ROOT .DS. '/user/data/snappygrav/wkhtmltopdf-i386' : GRAV_ROOT .DS. $wk_path );
          break;
        case 'plugin':
          $wk_path_prepend = GRAV_ROOT .DS. 'user/plugins/snappygrav/';
          $wk_path = ( empty($wk_path) ? $wk_path_prepend . 'vendor/h4cc/wkhtmltopdf-i386/bin/wkhtmltopdf-i386' : $wk_path_prepend . $wk_path );
          break;
        case 'os':
          $wk_path = ( empty($wk_path) ? '/usr/local/bin/wkhtmltopdf' : $wk_path );
          break;
      }

      // Check if wkhtmltopdf-i386 is executable
      if (file_exists($wk_path)) {
        $perms = fileperms( $wk_path );
        if($perms!=33261){
          @chmod($wk_path, 0755); //33261
        }
      }

      $wkpdf = new \Knp\Snappy\Pdf( $wk_path );
      
      if(!empty($metadata)){
        $wkpdf->setOption('title', isset($metadata['title']) ? $metadata['title'] : 'title' );
      }
      
      $wkpdf->setOption('margin-left', $pdf_option['left'] );
      $wkpdf->setOption('margin-top', $pdf_option['top'] );
      $wkpdf->setOption('margin-right', $pdf_option['right'] );
      $wkpdf->setOption('margin-bottom', $pdf_option['bottom'] );
      $wkpdf->setOption('page-size', $pdf_option['page_size'] );
      $wkpdf->setOption('encoding', $pdf_option['encoding'] );
      $wkpdf->setOption('orientation', $pdf_option['orientation'] );

      $grayscale = $config->get('plugins.snappygrav.grayscale');
      if($grayscale) $wkpdf->setOption('grayscale', true);

      $toc = $config->get('plugins.snappygrav.toc');
      if($toc) $wkpdf->setOption('toc', true);
      
      $zoom = $config->get('plugins.snappygrav.zoom');
      if($zoom) $wkpdf->setOption('zoom', $zoom);

      $print_media_type = $config->get('plugins.snappygrav.print_media_type');
      if($print_media_type) {
          $wkpdf->setOption('print-media-type',true);
      } else {
          $wkpdf->setOption('no-print-media-type',true);
      }

      $pdf = $wkpdf->getOutputFromHtml($html);
      
      return $pdf;
    }
}
