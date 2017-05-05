<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

use Grav\Common\Page\Page;
use Grav\Common\Page\Collection;
use Grav\Common\Uri;
use Grav\Common\Taxonomy;

use Knp\Snappy\Pdf;

class SnappyGravPlugin extends Plugin
{
    public static function getSubscribedEvents() {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
        ];
    }

    /**
     * Activate plugin if path matches to the configured one.
     */
    public function onPluginsInitialized()
    {
        if ($this->isAdmin()) {
            $this->active = false;
            return;
        }

        require_once __DIR__ . '/vendor/autoload.php';

        $this->enable([
            'onTwigInitialized'     => ['onTwigInitialized', 0],
        ]);
            
        /** @var Uri $uri */
        $uri = $this->grav['uri'];
        $params = $uri->params(null, true);

        //Get pdf or completepdf
        if ((count($params) > 0) && (reset($params) == "pdf" || reset($params) == "completepdf")){
            $this->enable([
                'onCollectionProcessed' => ['onCollectionProcessed', 0],
                //Twig
                'onTwigSiteVariables' => ['onTwigSiteVariables', 0],
                'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
            ]);
        }
    }

    /**
     * Add simple `thumbs()` Twig function
     */
    public function onTwigInitialized() {
        $this->grav['twig']->twig()->addFunction(
            new \Twig_SimpleFunction('snappygrav', [$this, 'generateLink'])
        );
    }

    public function generateLink($id=null, $options = [])
    {
        $param_sep = $this->grav['config']->get('system.param_sep', ':');

        $slug_blog = 'blog';
        $slug_blog = $this->config->get('plugins.snappygrav.slug_blog');

        $uri = $this->grav['uri'];
        $basename = $uri->basename();
        $current_theme = $this->grav['themes']->current();

        $language = $this->grav['language'];
        $single = $language->translate(['SNAPPYGRAV_PLUGIN.GET_PDF']);
        $full = $language->translate(['SNAPPYGRAV_PLUGIN.GET_FULL_PDF']);
        
        $html = '<a href="'.$this->grav['base_url'] . $id . $param_sep . 'pdf" title="'.$single.'"><i class="fa fa-file-pdf-o"></i></a>';
        if ($id === null) {
            $html = '<a href="'.$this->grav['base_url'] . DS . $param_sep . 'completepdf" title="'.$full.'"><i class="fa fa-file-pdf-o"></i></a>';
        } else {
            if ( ($current_theme == 'learn2') && ( empty($basename) ) ) {
                $html = '<a href="'.$this->grav['base_url'] . $id . $param_sep . 'pdf" title="'.$single.'"><i class="fa fa-file-pdf-o"></i></a>';
            }
            if ( $current_theme == 'antimatter' ) {
                $html = '<a href="'.$this->grav['base_url'] . DS . $slug_blog . DS . $id . $param_sep . 'pdf" title="'.$single.'"><i class="fa fa-file-pdf-o"></i></a>';
            }
        }

        return $html;
    }

    /**
     * Create pagination object for the page.
     *
     * @param Event $event
     */
    public function onCollectionProcessed(Event $event)
    {
        /** @var Collection $collection */
        $collection = $event['collection'];
        foreach ($collection as $slug => $page) {
            $header = $page->header();
            if (isset($header->feed) && !empty($header->feed['skip'])) {
                $collection->remove($page);
            }
        }

        $uri = $this->grav['uri'];
        $uri_params = $uri->params(null, true);

        //Exit if we get there with empty array (eg no :pdf / :completepdf params)
        if (count($uri_params) == 0) {
            return;
        }

        $option = reset($uri_params);
        $slug = key($uri_params);
        $html = [];
        $content = [];

        foreach ($collection as $page) {

            $page_route = $page->route();
            $pieces = explode("/", $page_route);
            $len = count($pieces);
            $target = $pieces[$len-1];

            if($slug == $target || $option == "completepdf"){
                //Page variables
                // get title
                $content['page_title'] = $page->title();

                //get date
                $page_serial = $page->date();
                $content['page_date'] = date("d-m-Y",$page_serial);

                //get author
                $content['page_header_author'] = '';
                if( isset( $page->header()->author ) ) {
                    $content['page_header_author'] = $page->header()->author;
                }

                //get content
                $content['page_content'] = $page->content();

                //get slug
                $content['page_slug'] = $page->slug();

                //get breadcrumbs
                $content['breadcrumbs'] = $this->get_crumbs( $page );

                /** @var Twig $twig */
                $twig = $this->grav['twig'];
                $this->grav['twig']->twig_vars['snappygrav'] = $content;
                $template = "snappygrav.html.twig";

                $html[] = $twig->processTemplate($template);
            }
        }

        $snappy = $this->set_snappy( $content );

        $filename = ( $option == "completepdf" ? parse_url($uri->base())['host'] : $content['page_slug'] );
        //Saves ie https problems
        header("Cache-Control: public");
        header("Content-Type: application/pdf");
        header('Pragma: private');
        header('Expires: 0');
        header('Content-Disposition: attachment; filename="'. $filename .'.pdf"');

        echo ($snappy->getOutputFromHtml($html));
    }


    public function get_crumbs( $page )
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


    /*
     * ottiene percorso del programma wkhtmltopdf
     * lo rende eseguibile se già non lo è
     * crea una nuova istanza
     * carica la configurazione da snappygrav.yaml
     * restituisce istanza
     */
    public function set_snappy( $content )
    {
        $matter = $content;
        // path del programma wkhtmltopdf
        $wk_path = __DIR__ .DS. $this->config->get('plugins.snappygrav.wk_path');
        if( (empty($wk_path)) || (!file_exists($wk_path)) ) $wk_path = __DIR__ .DS. 'vendor/h4cc/wkhtmltopdf-i386/bin/wkhtmltopdf-i386';

        // If the file does not exist displays an alert and exits the procedure
        if (!file_exists($wk_path)) {
            $message = 'The file\n '.$wk_path.'\n does not exist!';
            echo '<script type="text/javascript">alert("'.$message.'");</script>';
            break;
        }

        // Check if wkhtmltopdf-i386 is executable
        $perms = fileperms( $wk_path );
        if($perms!=33261){
            @chmod($wk_path, 0755); //33261
        }

        $snappy = new Pdf($wk_path);
        //$snappy = new \Knp\Snappy\Pdf($wk_path);

        // It takes some parameters from snappygrav.yaml file

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

        $hastitle = $this->config->get('plugins.snappygrav.title');
        if($hastitle) $snappy->setOption('title', $matter['page_title']);

        $toc = $this->config->get('plugins.snappygrav.toc');
        if($toc) $snappy->setOption('toc', true);
        
        $zoom = $this->config->get('plugins.snappygrav.zoom');
        if($zoom) $snappy->setOption('zoom', $zoom);

        return $snappy;
    }

    /**
     * Handles template and specific CSS for PDF template
     */
    public function onTwigSiteVariables() {
        if ($this->config->get('plugins.snappygrav.built_in_css')) {
            $this->grav['assets']->add('plugin://snappygrav/assets/css/snappygrav.css');
        }
    }

    /**
     * Add current directory to Twig lookup paths.
     */
    public function onTwigTemplatePaths()
    {
      $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }
}
