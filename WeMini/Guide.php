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

namespace WeMini;

use WeChat\Contracts\BasicWeChat;

/**
 * 小程序导购助手
 * @package WeMini
 */
class Guide extends BasicWeChat
{
    /**
     * 添加导购
     * @param array $data 导购账号信息
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function addGuideAcct($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/addguideacct?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 删除导购
     * @param array $data 导购账号
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function delGuideAcct($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/delguideacct?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 获取导购信息
     * @param array $data 导购账号
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getGuideAcct($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/getguideacct?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 获取敏感词与自动回复配置
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getGuideAcctConfig()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/getguideacctconfig?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, [], true);
    }

    /**
     * 拉取导购列表
     * @param int $page 页码
     * @param int $num 数量
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getGuideAcctList($page = 0, $num = 10)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/getguideacctconfig?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['page' => $page, 'num' => $num], true);
    }

    /**
     * 获取导购聊天记录
     * @param array $data 查询条件
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getGuideBuyerChatRecord($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/getguideacct?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 获取导购快捷回复
     * @param array $data 导购信息
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getGuideConfig($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/getguideconfig?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 生成导购二维码
     * @param array $data 导购信息
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function guideCreateQrCode($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/guidecreateqrcode?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 推送小程序路径菜单
     * @param array $data 请求参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function pushShowWxaPathMenu($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/pushshowwxapathmenu?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 设置敏感词与自动回复
     * @param array $data 配置数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function setGuideAcctConfig($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/setguideacctconfig?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 设置导购快捷回复
     * @param array $data 快捷回复数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function setGuideConfig($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/setguideconfig?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 更新导购昵称或头像
     * @param array $data 导购信息
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function updateGuideAcct($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/setguideconfig?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 添加展示标签
     * @param array $data 标签数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function addGuideBuyerDisplayTag($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/addguidebuyerdisplaytag?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 为粉丝添加可查询标签
     * @param array $data 标签数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function addGuideBuyerTag($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/addguidebuyertag?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 添加标签可选值
     * @param array $data 选项数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function addGuideTagOption($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/addguidetagoption?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 删除粉丝标签
     * @param array $data 标签数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function delGuideBuyerTag($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/delguidebuyertag?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 查询展示标签
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getGuideBuyerDisplayTag($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/getguidebuyerdisplaytag?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 查询粉丝标签
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getGuideBuyerTag($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/getguidebuyertag?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 查询标签可选值
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getGuideTagOption()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/getguidetagoption?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, [], true);
    }

    /**
     * 新建可查询标签类型
     * @param array $data 标签数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function newGuideTagOption($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/newguidetagoption?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 标签筛选粉丝
     * @param array $data 标签查询条件
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function queryGuideBuyerByTag($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/queryguidebuyerbytag?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 导购添加粉丝
     * @param array $data 绑定数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function addGuideBuyerRelation($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/addguidebuyerrelation?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 删除导购粉丝
     * @param array $data 解绑数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function delGuideBuyerRelation($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/delguidebuyerrelation?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 查询粉丝绑定关系
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getGuideBuyerRelation($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/getguidebuyerrelation?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 通过粉丝查询绑定关系
     * @param string $openid 粉丝 openid
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getGuideBuyerRelationByBuyer($openid)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/getguidebuyerrelation?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, ['openid' => $openid], true);
    }

    /**
     * 拉取导购粉丝列表
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getGuideBuyerRelationList($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/getguidebuyerrelationlist?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 粉丝迁移导购
     * @param array $data 迁移数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function rebindGuideAcctForBuyer($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/rebindguideacctforbuyer?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 更新粉丝昵称
     * @param array $data 昵称数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function updateGuideBuyerRelation($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/updateguidebuyerrelation?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 删除卡片素材
     * @param array $data 素材信息
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function delGuideCardMaterial($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/delguidecardmaterial?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 删除图片素材
     * @param array $data 素材信息
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function delGuideImageMaterial($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/delguideimagematerial?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 删除文字素材
     * @param array $data 素材信息
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function delGuideWordMaterial($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/delguidewordmaterial?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 获取卡片素材
     * @param int $type 类型
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getGuideCardMaterial($type = 0)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/getguidecardmaterial?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, ['type' => $type], true);
    }

    /**
     * 获取图片素材
     * @param int $type 类型
     * @param int $start 起始
     * @param int $num 数量
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getGuideImageMaterial($type = 0, $start = 0, $num = 10)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/getguideimagematerial?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, ['type' => $type, 'start' => $start, 'num' => $num], true);
    }

    /**
     * 获取文字素材
     * @param int $type 类型
     * @param int $start 起始
     * @param int $num 数量
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getGuideWordMaterial($type = 0, $start = 0, $num = 10)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/getguidewordmaterial?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, ['type' => $type, 'start' => $start, 'num' => $num], true);
    }

    /**
     * 添加卡片素材
     * @param array $data 素材数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function setGuideCardMaterial($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/setguidecardmaterial?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 添加图片素材
     * @param array $data 素材数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function setGuideImageMaterial($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/setguideimagematerial?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 添加文字素材
     * @param array $data 素材数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function setGuideWordMaterial($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/guide/setguidewordmaterial?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, $data, true);
    }
}