<?php

// +----------------------------------------------------------------------
// | WeChatDeveloper
// +----------------------------------------------------------------------
// | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/WeChatDeveloper
// | github 代码仓库：https://github.com/zoujingli/WeChatDeveloper
// +----------------------------------------------------------------------

namespace WeChat;

use WeChat\Contracts\BasicWeChat;
use WeChat\Contracts\Tools;
use WeChat\Exceptions\InvalidResponseException;

/**
 * 微信素材管理
 * @package WeChat
 */
class Media extends BasicWeChat
{
    /**
     * 新增临时素材
     * @param string $filename 本地文件路径
     * @param string $type 媒体类型 image|voice|video|thumb
     * @return array 上传结果（media_id 等）
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function add($filename, $type = 'image')
    {
        if (!in_array($type, ['image', 'voice', 'video', 'thumb'])) {
            throw new InvalidResponseException('Invalid Media Type.', '0');
        }
        $url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token=ACCESS_TOKEN&type={$type}";
        return $this->callPostApi($url, ['media' => Tools::createCurlFile($filename)], false);
    }

    /**
     * 获取临时素材
     * @param string $media_id 媒体 ID
     * @param string $outType 可选：回调处理或 'url' 仅返回 URL
     * @return array|string
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function get($media_id, $outType = null)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=ACCESS_TOKEN&media_id={$media_id}";
        $this->registerApi($url, __FUNCTION__, func_get_args());
        if ($outType == 'url') return $url;
        $result = Tools::get($url);
        if (is_array($json = json_decode($result, true))) {
            if (!$this->isTry && isset($json['errcode']) && in_array($json['errcode'], ['40014', '40001', '41001', '42001'])) {
                [$this->delAccessToken(), $this->isTry = true];
                return call_user_func_array([$this, $this->currentMethod['method']], $this->currentMethod['arguments']);
            }
            return Tools::json2arr($result);
        }
        return is_null($outType) ? $result : $outType($result);
    }

    /**
     * 新增永久图文素材
     * @param array $data 图文列表 articles
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function addNews($data)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/material/add_news?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 更新图文素材
     * @param string $media_id 图文 media_id
     * @param int $index 文章位置（0 开始）
     * @param array $news 文章内容
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function updateNews($media_id, $index, $news)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/material/update_news?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['media_id' => $media_id, 'index' => $index, 'articles' => $news]);
    }

    /**
     * 上传图文消息内的图片，获取 URL
     * @param string $filename 本地文件
     * @return array 含 url
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function uploadImg($filename)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['media' => Tools::createCurlFile($filename)], false);
    }

    /**
     * 新增其他类型永久素材
     * @param string $filename 本地文件路径
     * @param string $type 媒体类型 image|voice|video|thumb
     * @param array $description 视频描述等
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function addMaterial($filename, $type = 'image', $description = [])
    {
        if (!in_array($type, ['image', 'voice', 'video', 'thumb'])) {
            throw new InvalidResponseException('Invalid Media Type.', '0');
        }
        $url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=ACCESS_TOKEN&type={$type}";
        return $this->callPostApi($url, ['media' => Tools::createCurlFile($filename), 'description' => Tools::arr2json($description)], false);
    }

    /**
     * 获取永久素材
     * @param string $media_id 媒体 ID
     * @param null|string $outType 回调处理或 'url' 返回地址
     * @return array|string
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getMaterial($media_id, $outType = null)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/material/get_material?access_token=ACCESS_TOKEN";
        $this->registerApi($url, __FUNCTION__, func_get_args());
        if ($outType == 'url') return $url;
        $result = Tools::post($url, ['media_id' => $media_id]);
        if (is_array($json = json_decode($result, true))) {
            if (!$this->isTry && isset($json['errcode']) && in_array($json['errcode'], ['40014', '40001', '41001', '42001'])) {
                [$this->delAccessToken(), $this->isTry = true];
                return call_user_func_array([$this, $this->currentMethod['method']], $this->currentMethod['arguments']);
            }
            return Tools::json2arr($result);
        }
        return is_null($outType) ? $result : $outType($result);
    }

    /**
     * 删除永久素材
     * @param string $mediaId 媒体 ID
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function delMaterial($mediaId)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/material/del_material?access_token=ACCESS_TOKEN";
        return $this->httpPostForJson($url, ['media_id' => $mediaId]);
    }

    /**
     * 获取素材总数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getMaterialCount()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/material/get_materialcount?access_token=ACCESS_TOKEN";
        return $this->callGetApi($url);
    }

    /**
     * 获取素材列表
     * @param string $type image|voice|video|news
     * @param int $offset 起始位置
     * @param int $count 拉取数量
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function batchGetMaterial($type = 'image', $offset = 0, $count = 20)
    {
        if (!in_array($type, ['image', 'voice', 'video', 'news'])) {
            throw new InvalidResponseException('Invalid Media Type.', '0');
        }
        $url = "https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['type' => $type, 'offset' => $offset, 'count' => $count]);
    }
}