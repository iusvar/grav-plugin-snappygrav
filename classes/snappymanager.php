<?php

namespace Grav\Plugin\SnappyManager;
use Grav\Common\Grav;
use Grav\Common\Utils;
use RocketTheme\Toolbox\File\JsonFile;

class SnappyManager
{
    public $grav;
    public $json_response;
    protected $post;
    protected $task;

    protected $lang;

    public function __construct(Grav $grav)
    {
        $this->grav     = $grav;
        $this->config   = $this->grav['config'];
        $this->lang = $this->grav['language'];
    }


    public function setMessage($msg, $type = 'info')
    {
        $messages = $this->grav['messages'];
        $messages->add($msg, $type);
    }


    public function messages($type = null)
    {
        $messages = $this->grav['messages'];
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
        if (method_exists('Grav\Common\Utils', 'getNonce')) {
            if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
                if (isset($this->post['snappy-nonce'])) {
                    $nonce = $this->post['snappy-nonce'];
                } else {
                    $nonce = $this->grav['uri']->param('snappy-nonce');
                }

                if (!$nonce || !Utils::verifyNonce($nonce, 'snappy-form')) {
                    if ($this->task == 'addmedia') {

                        $message = sprintf($this->lang->translate('PLUGIN_ADMIN.FILE_TOO_LARGE', null),
                            ini_get('post_max_size'));

                        $this->json_response = [
                            'status'  => 'error',
                            'message' => $message
                        ];

                        return false;
                    }

                    $this->setMessage($this->lang->translate('PLUGIN_ADMIN.INVALID_SECURITY_TOKEN'), 'error');
                    $this->json_response = [
                        'status'  => 'error',
                        'title' => $this->lang->translate('PLUGIN_SNAPPYGRAV.INVALID_SECURITY_TOKEN_TITLE'),
                        'message' => $this->lang->translate('PLUGIN_SNAPPYGRAV.INVALID_SECURITY_TOKEN_TEXT')
                    ];

                    return false;
                }
                unset($this->post['snappy-nonce']);
            } else {
                $nonce = $this->grav['uri']->param('snappy-nonce');
                if (!isset($nonce) || !Utils::verifyNonce($nonce, 'snappy-form')) {
                    $this->setMessage($this->lang->translate('PLUGIN_SNAPPYGRAV.INVALID_SECURITY_TOKEN'), 'error');
                    $this->json_response = [
                        'status'  => 'error',
                        'title' => $this->lang->translate('PLUGIN_SNAPPYGRAV.INVALID_SECURITY_TOKEN_TITLE'),
                        'message' => $this->lang->translate('PLUGIN_SNAPPYGRAV.INVALID_SECURITY_TOKEN_TEXT')
                    ];
                    return false;
                }
            }
        }
        return true;
    }


    protected function taskSnappy()
    {
        $export_branch  = $this->grav['uri']->param('branch');
        $export_route   = $this->grav['uri']->param('route');
        $export_route   = str_replace('@','/',$export_route);
        $export_type    = $this->grav['uri']->param('type');

        //For now only the pdf format is handled
        if( $export_type ){
            if( $export_type != 'pdf' ){
                $this->json_response = [
                    'status'    => 'error',
                    'title'     => $this->lang->translate('PLUGIN_SNAPPYGRAV.CREATION_FAILED'),
                    'message'   => strtoupper($export_type) .' '. $this->lang->translate('PLUGIN_SNAPPYGRAV.NOT_YET_MANAGED') . '<br/>' . $this->lang->translate('PLUGIN_SNAPPYGRAV.WAIT_FOR_FUTURE_UPDATES')
                ];
                return true;
            }
        }

        try {
            $return_value = $this->makeDocument($export_route, $export_branch);
            $encoded_pdf = $return_value['encoded_pdf'];
            $filename = $return_value['filename'];
        } catch (\Exception $e) {
            $this->json_response = [
                'status'    => 'error',
                'title'     => 'Error 1',
                'message'   => $this->lang->translate('PLUGIN_SNAPPYGRAV.AN_ERROR_OCCURRED') . '. ' . $e->getMessage()
            ];
            return true;
        }

        $btn_plugin         = $this->lang->translate('PLUGIN_SNAPPYGRAV.BTN_PLUGIN');
        $inline_button      = $this->lang->translate('PLUGIN_SNAPPYGRAV.INLINE');
        $attachment_button  = $this->lang->translate('PLUGIN_SNAPPYGRAV.ATTACHMENT');
        $message            = $this->lang->translate('PLUGIN_SNAPPYGRAV.YOUR_DOCUMENT_IS_READY_FOR');
        $message            = str_replace('%1', strtoupper($export_type), $message);

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
        $page = $this->grav['page'];
        $twig = $this->grav['twig'];
        $parameters = [];
        $html = [];
        $filename = 'completepdf';
        $temp_html = '';
        
        if( !empty($route) ) { //single or branch
            $found = $page->find( $route );
            $parameters['branch'] = ($branch == 'yes' ? true: false);
            $parameters['breadcrumbs']  = $this->get_crumbs( $found );
            $filename = $found->title();
            $temp_html = $twig->processTemplate('snappygrav.html.twig', ['page' => $found, 'parameters' => $parameters]);
            $html[] = preg_replace('/<iframe>.*<\/iframe>/is', '', $temp_html);
        }

        if( empty($route) ) { //completepdf

            $current_theme = $this->grav['themes']->current();
            switch ($current_theme) {
                case 'antimatter':
                    $where = DS . $this->config->get('plugins.snappygrav.slug_blog');
                    if(empty($where)) $where = DS . 'blog';
                    $my_path='@page.children';
                    break;
                case 'knowledge-base':
                    $where = $this->grav['config']->get('themes.knowledge-base')['params']['articles']['root'];
                    if(empty($where)) $where = DS . 'home';
                    $my_path='@page.children';
                    break;
                case 'learn2':
                    $where = DS;
                    $my_path='@root.descendants'; //see https://learn.getgrav.org/content/collections
                    break;
                case 'learn2-git-sync':
                    $where = DS;
                    $my_path='@root.descendants';
                default:
                    $where = DS;
                    $my_path='@root.descendants';
                    break;
            }
            $page_children = $page->evaluate([$my_path => $where ]);
            $collection = $page_children;
            /*if( $current_theme != 'learn2' && $current_theme != 'learn3' ){
                $collection = $page_children->order('date', 'desc');
            }*/ /* evaluate */

            foreach ($collection as $page) {
              
                $parameters['breadcrumbs']  = $this->get_crumbs( $page );
                $parameters['branch']       = ($branch == 'yes' ? true : false );
                $temp_html = $twig->processTemplate('snappygrav.html.twig', ['page' => $page, 'parameters' => $parameters]);
                $html[] = preg_replace('/<iframe>.*<\/iframe>/is', '', $temp_html);
            }
        }
        
        $encoded_pdf = $this->runWk( $html );

        $return_value = array();
        $return_value['encoded_pdf'] = $encoded_pdf;
        $return_value['filename'] = $filename;

        return $return_value;
    }


    protected function get_crumbs( $page )
    {
        $current = $page;
        $hierarchy = array();
        while ($current && !$current->root()) {
            $hierarchy[$current->url()] = $current;
            $current = $current->parent();
        }
        $home = $this->grav['pages']->dispatch('/');
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


    protected function runWk( $html )
    {
        // Placement/Path of the wkhtmltopdf program
        $wk_absolute_pos = ( $this->config->get('plugins.snappygrav.wk_absolute_pos') ? true : false );
        $wk_path = $this->config->get('plugins.snappygrav.wk_path');
        if( $wk_absolute_pos ) {
            //true, absolute, under o.s.
            $wk_path = ( empty($wk_path) ? '/usr/local/bin/wkhtmltopdf' : $wk_path );
        } else {
            //false, relative, under plugin
            $wk_path_prepend = GRAV_ROOT .'/user/plugins/snappygrav/';
            $wk_path = ( empty($wk_path) ? $wk_path_prepend . 'vendor/h4cc/wkhtmltopdf-i386/bin/wkhtmltopdf-i386' : $wk_path_prepend . $wk_path );
        }
        
        //$wk_path = ROOT_DIR .'user/plugins/snappygrav/'. $this->config->get('plugins.snappygrav.wk_path');
        //if( (empty($wk_path)) || (!file_exists($wk_path)) ) $wk_path = ROOT_DIR .'user/plugins/snappygrav/'. 'vendor/h4cc/wkhtmltopdf-i386/bin/wkhtmltopdf-i386';

        // Check if wkhtmltopdf-i386 is executable
        if (file_exists($wk_path)) {
            $perms = fileperms( $wk_path );
            if($perms!=33261){
                @chmod($wk_path, 0755); //33261
            }
        }

        // If the file does not exist displays an alert and exits the procedure
        /*if (!file_exists($wk_path)) {
            $message = 'The file\n '.$wk_path.'\n does not exist!';
            echo '<script type="text/javascript">alert("'.$message.'");</script>';
            break;
        }*/

        $snappy = new \Knp\Snappy\Pdf( $wk_path );
        
        //It takes some parameters from snappygrav.yaml file
        //$snappy->setOption('default-header', true);
        //$snappy->setOption('header-left',$matter['page_title']);
        //$snappy->setOption('header-right','[page]/[toPage]');
        //$snappy->setOption('header-spacing',5);
        //$snappy->setOption('header-line',true);
         
        $grayscale = $this->config->get('plugins.snappygrav.grayscale');
        if($grayscale) $snappy->setOption('grayscale', $grayscale);
        
        $margin_bottom = $this->config->get('plugins.snappygrav.margin_bottom');
        if($margin_bottom) $snappy->setOption('margin-bottom', $margin_bottom);
        
        $margin_left = $this->config->get('plugins.snappygrav.margin_left');
        if($margin_left) $snappy->setOption('margin-left', $margin_left);
        
        $margin_right = $this->config->get('plugins.snappygrav.margin_right');
        if($margin_right) $snappy->setOption('margin-right', $margin_right);
        
        $margin_top = $this->config->get('plugins.snappygrav.margin_top');
        if($margin_top) $snappy->setOption('margin-top', $margin_top);
        
        $orientation = $this->config->get('plugins.snappygrav.orientation');
        if($orientation == "Portrait" || $orientation == "Landscape") {
            $snappy->setOption('orientation', $orientation);
        }
        
        $page_size = $this->config->get('plugins.snappygrav.page_size');
        if($page_size) $snappy->setOption('page-size', $page_size);
        
        //$hastitle = $this->config->get('plugins.snappygrav.title');
        //if($hastitle) $snappy->setOption('title', $matter['page_title']);
        
        $toc = $this->config->get('plugins.snappygrav.toc');
        if($toc) $snappy->setOption('toc', true);
        
        $zoom = $this->config->get('plugins.snappygrav.zoom');
        if($zoom) $snappy->setOption('zoom', $zoom);

        $print_media_type = $this->config->get('plugins.snappygrav.print_media_type');
        if($print_media_type) {
            $snappy->setOption('print-media-type',true);
        } else {
            $snappy->setOption('no-print-media-type',true);
        }

        $pdf = $snappy->getOutputFromHtml($html);
        $encoded_pdf = base64_encode($pdf);
        
        return $encoded_pdf;
    }

}
