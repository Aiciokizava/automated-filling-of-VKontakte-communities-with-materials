<?php

require_once('config.php');

class VK
{
    private $group_id = GROUP_ID;
    private $user_token = USER_TOKEN;
    private $version = VERSION;

    // Отправка запроса с ключом доступа пользователя
    public function call_with_user_token($method, $params = []) {
        $params['access_token'] = $this->user_token;
        $params['v'] = $this->version;
        $url = 'https://api.vk.com/method/'.$method.'?'.http_build_query($params);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($curl);
        curl_close($curl);
        return json_decode($json);
    }

    // Загрузка изображений на сервер ВКонтакте
    public function photos_upload_server($url, $image_path) {
        $params['photo'] = new CURLFile($image_path);
        $params['access_token'] = $this->user_token;
        $params['v'] = $this->version;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        $json = curl_exec($curl);
        curl_close($curl);
        return json_decode($json);
    }

    // Методы ВКонтакте
    // Для публикации записи
    public function wall_post($message, $attachments, $publish_date) {
        return $this->call_with_user_token('wall.post', [
            'owner_id' => $this->group_id,
            'message' => $message,
            'attachments' => $attachments,
            'publish_date' => $publish_date
        ]);
    }

    // Получение информации о записях на стене сообщестсва
    public function wall_get($owner_id, $count, $extended) {
        return $this->call_with_user_token('wall.get', [
            'owner_id' => $owner_id,
            'count' => $count,
            'extended' => $extended
        ]);
    }

    // Получение ссылки для загрузки
    public function photos_get_wall_upload_server() {
        return $this->call_with_user_token('photos.getWallUploadServer', [
            'group_id' => $this->group_id * -1
        ]);
    }

    // Сохранение фотографии ВКонтакте
    public function photos_save_wall_photo($photo, $server, $hash) {
        return $this->call_with_user_token('photos.saveWallPhoto', [
            'group_id' => $this->group_id * -1,
            'photo' => $photo,
            'server' => $server,
            'hash' => $hash
        ]);
    }
}