<?php

require_once(__DIR__.'/vk.php');
$vk = new VK();

require_once(__DIR__.'/config.php');
$forbidden_words_in_the_post = FORBIDDEN_WORDS_IN_THE_POST;
$owner_id = USER_ID;

$mysqli = new mysqli('localhost', 'user', 'password_for_user', 'set_source');

function get_group_id_source($mysqli)
{
    $group_id_source = $mysqli->query("SELECT `group_id` FROM `set_source`.`source` WHERE `used` = '0' ORDER BY RAND() LIMIT 1");
    return $group_id_source->fetch_row();
}

$group_id_source = get_group_id_source($mysqli);
if (!date(G) or !$group_id_source[0])
{
    $mysqli->query("UPDATE `set_source`.`source` SET `used` = '0' WHERE `source`.`used` = 1;");
    $group_id_source = get_group_id_source($mysqli);
}

while ($group_id_source[0])
{
    $mysqli->query("UPDATE `set_source`.`source` SET `used` = '1' WHERE `group_id` = '$group_id_source[0]';");

    $wall_get = $vk->wall_get($group_id_source[0], 2, 1);
    $attachments_length = count($wall_get->response->items[1]->attachments);
    $text = $wall_get->response->items[1]->text;

    for ($i = 0; $i < count($forbidden_words_in_the_post); $i++)
        if (strpos($text, $forbidden_words_in_the_post[$i]) !== false)
        {
            $text = 'check';
            break;
        }
    
    if ($attachments_length and $text != 'check' and !$wall_get->response->items[1]->marked_as_ads and !$wall_get->response->items[1]->copyright->link)
    {
        array_map('unlink', glob(__DIR__.'/image/*'));

        $attachments = '';
        for ($i = 0; $i < $attachments_length; ++$i)
            if ($wall_get->response->items[1]->attachments[$i]->type == 'photo')
            {
                $image_file = file_get_contents($wall_get->response->items[1]->attachments[$i]->photo->sizes[count($wall_get->response->items[1]->attachments[$i]->photo->sizes) - 1]->url);
                file_put_contents(__DIR__.'/image/' . $i . '.jpg', $image_file);

                $photos_get_wall_upload_server = $vk->photos_get_wall_upload_server();
                $photos_upload_server = $vk->photos_upload_server($photos_get_wall_upload_server->response->upload_url, __DIR__.'/image/' . $i . '.jpg');
                $photos_save_wall_photo = $vk->photos_save_wall_photo($photos_upload_server->photo, $photos_upload_server->server, $photos_upload_server->hash);
                $attachments = $attachments . 'photo' . $owner_id . '_' . $photos_save_wall_photo->response[0]->id . ',';
            }

        $vk->wall_post('', $attachments, date(U) + 86400);

        break;
    }

    $group_id_source = get_group_id_source($mysqli);
}