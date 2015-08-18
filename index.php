<?php
/*
Plugin Name: URL-access
Description: Доступ к постам по URL
GitHub Plugin URI: https://github.com/systemo-biz/url-access
GitHub Branch: master
Version: 20150816-2
*/

add_action('wp_ajax_enable_key_access', 'enable_key_access');
add_action('wp_ajax_delete_key_access', 'delete_key_access');

function enable_key_access(){
        $new_key=wp_generate_password(12,false);
        update_post_meta($_REQUEST['post_id'],'access_key',$new_key);
        echo $new_key;
        exit;
}

function delete_key_access(){
        delete_post_meta($_REQUEST['post_id'],'access_key');
        exit;
}

function add_topic_access_key(){
        if(is_singular('topic')){
                $topic_url= get_permalink(bbp_get_topic_id());
                $key=get_post_meta(bbp_get_topic_id(),'access_key',true);
                $isKey=!empty($key);

                //Если ключ доступа не верен - редирект на главную
                if($isKey && !current_user_can('administrator') && $_GET['key']!=$key){
                        header("Location: /");
                        exit;
                }

                //Подключаем плагин zClip, который понадобится для копирования ссылки в буфер обмена
                wp_enqueue_script( 'zClip', plugin_dir_url(__FILE__).'zClip/ZeroClipboard.js');
                ?>
                <style>
                #toggle{
                        outline: none;
                }
                #access-link,#copy{
                        display: <?php echo $isKey?"inline":"none";?>;
                }
                #access-link{
                        height:30px;
                }
                #copy{
                        margin-left: 65px;
                }
                </style>
                <script>
                jQuery(document).ready(function($){
                        //Копирование ссылки
                        var client = new ZeroClipboard($("#copy"), {
                                moviePath: "<?php echo plugin_dir_url(__FILE__).'zClip/ZeroClipboard.swf';?>"
                        });

                        client.on("load", function(client) {
                                client.on("complete", function(client, args) {
                                        alert('Ссылка была скопирована в буфер обмена.');
                                });
                        });
                        //Переключатель
                                $("#toggle").click(function(){
                                        var thisForAjax=$(this);
                                        if($(this).text()=="Выкл"){
                                                $.ajax({
                                                data: ({
                                                action: 'enable_key_access',
                                                post_id: <?php bbp_topic_id(); ?>,
                                                }),
                                                url: "<?php echo admin_url('admin-ajax.php') ?>",
                                                success: function(data){
                                                        thisForAjax.removeClass('btn-default').addClass('btn-warning').text("Вкл");
                                                        $('#access-link,#copy').show();
                                                        $('#access-link').val($('#access-link').val()+'?key='+data).select();
                                                }
                                        });
                                        }else{
                                                $.ajax({
                                                        data: ({
                                                        action: 'delete_key_access',
                                                        post_id: <?php bbp_topic_id(); ?>,
                                                        }),
                                                        url: "<?php echo admin_url('admin-ajax.php') ?>",
                                                        success: function(){
                                                                thisForAjax.removeClass('btn-warning').addClass('btn-default').text("Выкл");
                                                                $('#access-link,#copy').hide();
                                                                $('#access-link').val('<?php echo $topic_url;?>');
                                                        }
                                                });
                                        }

                                });
                })
                </script>
                <p><b>Поделиться ссылкой</b></p>
                <div>
                <button type="button" id="toggle" class="btn <?php echo $isKey?"btn-warning":"btn-default";?> btn-sm"><?php echo $isKey?"Вкл":"Выкл";?></button>
                <input type="text" id="access-link" autocomplete="off" name="access-link" value="<?php echo $topic_url; echo $isKey?"?key=$key":"";?>">
                </div>
                <button id="copy" data-clipboard-target="access-link" class="btn btn-default btn-sm">Копировать ссылку</button>
                <?php
        }
}

add_shortcode('topic_access_key','add_topic_access_key');
