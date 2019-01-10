<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Utils;
use Grav\Plugin\SnappyManager;


class SnappyGravPlugin extends Plugin
{
    protected $lang;
    
    protected $route = "snappy-manager";
    protected $uri;
    protected $base;
    protected $admin_route;
    protected $snappymanager;
    protected $taskcontroller;
    protected $json_response = [];

    protected $listing = array();


    public static function getSubscribedEvents() {
        return [
            'onPluginsInitialized' => [['setting', 1000],['onPluginsInitialized', 0]],
        ];
    }

    public function setting()
    {
        //ini_set('xdebug.max_nesting_level', 512);

        $set_time_limit = $this->config->get('plugins.snappygrav.set_time_limit');
        set_time_limit($set_time_limit);

        require_once __DIR__ . '/classes/snappymanager.php';
        
        $this->lang = $this->grav['language'];
        $this->uri = $this->grav['uri'];
    }

    /**
     * Activate plugin if path matches to the configured one.
     */
    public function onPluginsInitialized()
    {
        /*if ($this->isAdmin()) {
            return;
        }*/

        require_once __DIR__ . '/vendor/autoload.php';

        $this->enable([
            //'onOutputGenerated' => ['onOutputGenerated'],
            'onTwigInitialized'     => ['onTwigInitialized', 0],
            'onPageInitialized'     => ['onPageInitialized', 1001],
            'onTwigSiteVariables'   => ['onTwigSiteVariables', 0],
            'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0]
        ]);

        $this->snappymanager = new SnappyManager($this->grav);
        $this->snappymanager->json_response = [];
        $this->grav['snappymanager'] = $this->snappymanager;
    }

    /*public function onOutputGenerated()
    {
      $page = $this->grav['page'];
      $this->grav['debugger']->addMessage( $page );
    }*/

    /**
     * Add current directory to Twig lookup paths.
     */
    public function onTwigTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }


    public function onPageInitialized()
    {
        $post = !empty($_POST) ? $_POST : [];

        $task = !empty($post['snappytask']) ? $post['snappytask'] : $this->uri->param('snappytask');

        if ($task) {
            $home = '/' . trim($this->config->get('system.home.alias'), '/');
            $pages = $this->grav['pages'];
            $this->grav['snappymanager']->routes = $pages->routes();

            if (isset($this->grav['snappymanager']->routes['/'])) {
                unset($this->grav['snappymanager']->routes['/']);
            }
            $page = $pages->dispatch('/', true);

            if ($page) {
                $page->route($home);
            }

            $this->snappymanager->execute($task, $post);
            if ( $this->snappymanager->json_response ) {
                echo json_encode($this->snappymanager->json_response);
                exit();
            }
        } else {
        }
    }


    public function onTwigInitialized()
    {
        $this->grav['twig']->twig()->addFunction(
            new \Twig_SimpleFunction('snappygrav', [$this, 'generateLink'])
        );

        $this->grav['twig']->twig()->addFilter(
            new \Twig_SimpleFilter('snappy_implode', 'implode')
        );
    }

    /**
     * Handles template and specific CSS for PDF template
     */
    public function onTwigSiteVariables()
    {
        $this->grav['assets']->add('plugin://snappygrav/assets/js/FileSaver.js');
        $this->grav['assets']->add('plugin://snappygrav/assets/js/base64-binary.js');
        $this->grav['assets']->add('plugin://snappygrav/assets/jquery-confirm-v3/css/jquery-confirm.css');
        $this->grav['assets']->add('plugin://snappygrav/assets/jquery-confirm-v3/js/jquery-confirm.js');
        $this->grav['assets']->add('plugin://snappygrav/assets/css/snappygrav.css');
    }


    public function generateLink($route = null, $options = [])
    {
        $snappy_bur = $this->grav['base_url_relative'] . DS;
        $snappy_manager = $this->route.'.json' . DS;
        $snappy_task = 'snappytask:snappy' . DS;

        //complete
        $esc_route = "";
        $cid = "";
        $button_id = "complete";

        $btn_title = $this->lang->translate('PLUGIN_SNAPPYGRAV.ENTIRE_SITE_EXPORT');
        //single or branch
        if(!empty($route)){
            $esc_route  = 'route:'.str_replace('/','@',$route) . DS;
            $id = Utils::getNonce($route);
            $cid = "id:" . $id . DS;
            $button_id = "sg-".$id;
            $btn_title = $this->lang->translate('PLUGIN_SNAPPYGRAV.THIS_PAGE_EXPORT');
        }

        $nonce = Utils::getNonce('snappy-form');
        $snappy_nonce = 'snappy-nonce:'.$nonce;
        
        $branch = $this->config->get('plugins.snappygrav.branch_enabled');
        $theme = $this->config->get('plugins.snappygrav.theme');

        $branch_value = 'branch:'.($branch ? 'yes' : 'no') . DS;
        $branch_btn_text = $this->lang->translate('PLUGIN_SNAPPYGRAV.COLLECT_BRANCH');

        $checked = ($branch ? 'checked' : '');

        $btn_data = $snappy_bur . $snappy_manager . $snappy_task . $branch_value . $esc_route . $snappy_nonce;

        $option_label = $this->lang->translate('PLUGIN_SNAPPYGRAV.OPTIONS');

        $btn_content = '';
        $current_theme = $this->grav['themes']->current();

        if( $current_theme=='learn2' || $current_theme=='learn3' || $current_theme=='learn2-git-sync' ){
            if( substr($button_id,0,2) == 'sg' ){
                $btn_title = $this->lang->translate('PLUGIN_SNAPPYGRAV.SINGLE_OR_BRANCH_EXPORT');
                $btn_content .= '<fieldset>';
                $btn_content .= '<legend>'.$option_label.'</legend>';
                $btn_content .= '<input type=\"checkbox\" id=\"enableCheckbox\" '.$checked.'>';
                $btn_content .= '<label for=\"enableCheckbox\">'.$branch_btn_text.'</label>';
                $btn_content .= '</fieldset>';
            }
        }

        $btn_title .= '<input id=\"export-pdf\" type=\"hidden\">';
        $btn_title .= '<input id=\"export-filename\" type=\"hidden\">';

        $awesome_icon = $this->config->get('plugins.snappygrav.export_button_icon');
        $export_button_icon = ( empty($awesome_icon) ? "" : "<i class=\"".$awesome_icon."\" aria-hidden=true></i>" );

        $export_button_text = $this->lang->translate('PLUGIN_SNAPPYGRAV.EXPORT_BUTTON');
        $space = ( !empty($export_button_icon) && !empty($export_button_text) ? '&nbsp;' : '' );
        
        $btn_export_text    = $this->lang->translate('PLUGIN_SNAPPYGRAV.BUTTON_EXPORT_TEXT');
        $btn_export_color   = $this->config->get('plugins.snappygrav.btn_export_color');
        $btn_cancel_text    = $this->lang->translate('PLUGIN_SNAPPYGRAV.BUTTON_CANCEL_TEXT');
        $btn_cancel_color   = $this->config->get('plugins.snappygrav.btn_cancel_color');
        $btn_inline_text    = $this->lang->translate('PLUGIN_SNAPPYGRAV.BUTTON_INLINE_TEXT');
        $btn_attach_text    = $this->lang->translate('PLUGIN_SNAPPYGRAV.BUTTON_ATTACH_TEXT');

        $html = '
            <button id="' . $button_id . '" class="export" type="button">' . $export_button_icon . $space . $export_button_text . '</button>
            <script>

                $(document).ready(function() {
                    
                    $("#'.$button_id.'").on("click", function () {
                        var jc = $.confirm({
                            theme: "'.$theme.'",
                            closeIcon: true,
                            closeIconClass: "fa fa-close",
                            icon: \''.$awesome_icon.'\',
                            useBootstrap: false,
                            title: "'.$btn_title.'",
                            content: "'.$btn_content.'",
                            buttons: {
                                attachment: {
                                    text: "'.$btn_attach_text.'",
                                    btnClass: "btn-purple",
                                    action: function() {
                                        var encoded_pdf = $("#export-pdf").val();
                                        var filename = $("#export-filename").val();
                                        var byteArray = Base64Binary.decodeArrayBuffer(encoded_pdf);
                                        var blob = new Blob([byteArray], { type: "application/pdf" });
                                        saveAs(blob, filename);
                                        return true;
                                    }
                                },
                                export: {
                                    btnData: "data-snappy=\"'.$btn_data.'\"",
                                    text: "'.$btn_export_text.'",
                                    btnClass: "btn-'.$btn_export_color.'",
                                    btnId: "'.$button_id.'-export",
                                    action: function () {

                                        // DISABLE BUTTON, CHANGE AWESOME ICON
                                        var awesome_element = $(".jconfirm-title-c").find(":first-child")[1];
                                        var awesome_element_class = awesome_element.className;
                                        $( awesome_element ).addClass(\'fa-spin fa-spinner\');
                                        this.buttons.export.disable();

                                        // GET BUTTON URL
                                        var element = $("button#'.$button_id.'-export");
                                        var url = element.attr("data-snappy");

                                        // EVALUATES BRANCH CHECKBOX
                                        var $checkbox = this.$content.find("#enableCheckbox");
                                        if($checkbox.prop("checked")){
                                            url = url.replace("branch:no","branch:yes");
                                        } else {
                                            url = url.replace("branch:yes","branch:no");
                                        }
                                        element.attr("data-snappy", url);

                                        $.ajax({
                                            url: url,
                                            dataType: "json",
                                            method: "get"
                                        }).done(function (data) {
                                            if (data.status == "success") {
                                                $("#export-pdf").val(data.encoded_pdf);
                                                $("#export-filename").val(data.filename);
                                                jc.buttons.attachment.show();
                                                jc.buttons.export.hide();
                                                jc.setContent(data.message);
                                            } else {
                                                $.confirm({
                                                    useBootstrap: false,
                                                    title: data.title,
                                                    type: "red",
                                                    content: data.message,
                                                    //autoClose: "cancelAction|10000",
                                                    escapeKey: "cancelAction",
                                                    buttons: {
                                                        cancelAction: {
                                                            text: "Cancel"
                                                        }
                                                    }
                                                });
                                            }
                                        }).fail(function(jqXHR, textStatus, errorMsg){
                                            $.alert({
                                                title: "Error 315",
                                                content: errorMsg,
                                            });
                                        }).always(function(){
                                            $(".jconfirm-title-c .fa-spin").removeClass(\'fa-spin fa-spinner\').addClass(awesome_element_class);
                                            element.prop( "disabled", false );
                                        });

                                        return false;
                                    }
                                },
                                close: {
                                    text: "'.$btn_cancel_text.'",
                                    btnClass: "btn-'.$btn_cancel_color.'",
                                    btnId: "'.$button_id.'-close",
                                    action: function () {
                                        return true;
                                    }
                                }
                            },
                            onOpenBefore: function() {
                                this.buttons.attachment.hide();
                            },
                            onOpen: function() {
                                var that = this;
                                this.$content.find("button").click(function () {
                                    that.$content.find("span").append("<br>This is awesome!!!!");
                                });

                                //INITIAL VALUE OF THE BRANCH CHECKBOX
                                var element = $("button#'.$button_id.'-export");
                                var url = element.attr("data-snappy");
                                var $checkbox = this.$content.find("#enableCheckbox");
                                if($checkbox.prop("checked")){
                                    url = url.replace("branch:no","branch:yes");
                                } else {
                                    url = url.replace("branch:yes","branch:no");
                                }
                                element.attr("data-snappy", url);
                            }
                        });
                    });
                        
                });
            </script>
            ';

        return $html;
    }

}
