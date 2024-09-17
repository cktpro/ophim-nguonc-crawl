<?php

namespace Ophim\Crawler\OphimCrawler;

use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Storage;

class Collector
{
    protected $fields;
    protected $payload;
    protected $forceUpdate;

    public function __construct(array $payload, array $fields, $forceUpdate)
    {
        $this->fields = $fields;
        $this->payload = $payload;
        $this->forceUpdate = $forceUpdate;
    }
// get nguonc
public function get_nguonc(): array
    {
        $info = $this->payload['movie'] ?? [];
        $episodes = $this->payload['movie']['episodes'] ?? [];

        $data = [
            'name' => $info['name'],
            'origin_name' => $info['original_name'],
            'publish_year' => $info['category']['3']['list'][0]['name'],
            'content' => $info['description'],
            'type' =>  $this->getMovieTypeNguonc($info, $episodes),
            'status' => $this->getStatusNguonc($info['current_episode']),
            // 'status' => 'completed',
            'thumb_url' => $this->getThumbImage($info['slug'], $info['thumb_url']),
            'poster_url' => $this->getPosterImage($info['slug'], $info['poster_url']),
            'is_copyright' => $info['is_copyright'] ?? false,
            'trailer_url' => $info['trailer_url'] ?? "",
            'quality' => $info['quality'],
            'language' => $info['language'],
            'episode_time' => $info['time'],
            'episode_current' => $info['current_episode'],
            'episode_total' => $info['total_episodes'],
            'notify' => $info['notify'] ?? "",
            'showtimes' => $info['showtimes'] ?? "",
            'is_shown_in_theater' => $info['chieurap'] ?? false,
        ];

        return $data;
    }
// end get nguonc
    public function get(): array
    {
        $info = $this->payload['movie'] ?? [];
        $episodes = $this->payload['episodes'] ?? [];

        $data = [
            'name' => $info['name'],
            'origin_name' => $info['origin_name'],
            'publish_year' => $info['year'],
            'content' => $info['content'],
            'type' =>  $this->getMovieType($info, $episodes),
            'status' => $info['status'],
            'thumb_url' => $this->getThumbImage($info['slug'], $info['thumb_url']),
            'poster_url' => $this->getPosterImage($info['slug'], $info['poster_url']),
            'is_copyright' => $info['is_copyright'],
            'trailer_url' => $info['trailer_url'] ?? "",
            'quality' => $info['quality'],
            'language' => $info['lang'],
            'episode_time' => $info['time'],
            'episode_current' => $info['episode_current'],
            'episode_total' => $info['episode_total'],
            'notify' => $info['notify'],
            'showtimes' => $info['showtimes'],
            'is_shown_in_theater' => $info['chieurap'],
        ];

        return $data;
    }

    public function getThumbImage($slug, $url)
    {
        return $this->getImage(
            $slug,
            $url,
            Option::get('should_resize_thumb', false),
            Option::get('resize_thumb_width'),
            Option::get('resize_thumb_height')
        );
    }

    public function getPosterImage($slug, $url)
    {
        return $this->getImage(
            $slug,
            $url,
            Option::get('should_resize_poster', false),
            Option::get('resize_poster_width'),
            Option::get('resize_poster_height')
        );
    }
    // GetStatusNguonc
   protected function slugify($str, $divider = '-')
    {
        $str = trim(mb_strtolower($str));
        $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
        $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
        $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
        $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
        $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
        $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
        $str = preg_replace('/(đ)/', 'd', $str);
        $str = preg_replace('/[^a-z0-9-\s]/', '', $str);
        $str = preg_replace('/([\s]+)/', $divider, $str);
        return $str;
    }
    protected function getStatusNguonc($status)
    {
        $slugifyStatus = $this->slugify($status,'_');
        $type = 'completed';
        if(strpos($slugifyStatus, 'tap')!==false || strpos($slugifyStatus, 'dang')!==false) {
            $type = 'ongoing';
        } elseif(strpos($slugifyStatus, 'hoan')!==false || strpos($slugifyStatus, 'full')!==false) {
            $type = 'completed';
        }else{
            $type = 'is_trailer';
        };
        return $type;


    }


    // Get type nguonc
    protected function getMovieTypeNguonc($info, $episodes)
    {
        return $info['category']['1']['list'][0]['name'] == 'Phim bộ' ? 'series'
            : 'single';
    }
    // End get type nguonc
    protected function getMovieType($info, $episodes)
    {
        return $info['type'] == 'series' ? 'series'
            : ($info['type'] == 'single' ? 'single'
                : (count(reset($episodes)['server_data'] ?? []) > 1 ? 'series' : 'single'));
    }

    protected function getImage($slug, string $url, $shouldResize = false, $width = null, $height = null): string
    {
        if (!Option::get('download_image', false) || empty($url)) {
            return $url;
        }
        try {
            $url = strtok($url, '?');
            $filename = substr($url, strrpos($url, '/') + 1);
            $path = "images/{$slug}/{$filename}";

            if (Storage::disk('public')->exists($path) && $this->forceUpdate == false) {
                return Storage::url($path);
            }

            // Khởi tạo curl để tải về hình ảnh
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36");
            $image_data = curl_exec($ch);
            curl_close($ch);

            $img = Image::make($image_data);

            if ($shouldResize) {
                $img->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }

            Storage::disk('public')->put($path, null);

            $img->save(storage_path("app/public/" . $path));

            return Storage::url($path);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $url;
        }
    }
}
