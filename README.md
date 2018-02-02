[![Latest Unstable Version](https://poser.pugx.org/zoujingli/wechat-developer/v/unstable)](https://packagist.org/packages/zoujingli/wechat-developer)·[![Latest Stable Version](https://poser.pugx.org/zoujingli/wechat-developer/v/stable)](https://packagist.org/packages/wechat-developer)·[![Total Downloads](https://poser.pugx.org/zoujingli/wechat-developer/downloads)](https://packagist.org/packages/wechat-developer)·[![License](https://poser.pugx.org/zoujingli/wechat-developer/license)](https://packagist.org/packages/wechat-developer)

WeChatDeveloper for PHP
--
* WeChatDeveloper 是基于 [wechat-php-sdk](https://github.com/zoujingli/wechat-php-sdk) 的重构，优化并完善；
* 微信 SDK 运行最底要求 PHP 版本 5.4 , 建议在 PHP7 上运行以获取最佳性能；
* WeChatDeveloper 针对 access_token 失效增加了自动刷新机制；
* 微信的部分接口需要缓存数据在本地，因此对目录需要有写权限；
* 我们鼓励大家使用 composer 来管理您的第三方库，方便后期更新操作；
* WeChatDeveloper 已历经数个线上项目考验，欢迎 fork 或 star 此项目。


Documentation and Help
--
PHP开发技术交流（QQ群 513350915）

[![PHP微信开发群 (SDK)](http://pub.idqqimg.com/wpa/images/group.png)](http://shang.qq.com/wpa/qunwpa?idkey=ae25cf789dafbef62e50a980ffc31242f150bc61a61164458216dd98c411832a) 

> WeChatDeveloper 是基于官方接口封装，在做微信开发前，必需先阅读微信官方文档。
>* 微信官方文档： http://mp.weixin.qq.com/wiki
>* 商户支付文档：https://pay.weixin.qq.com/wiki/doc/api/index.html

> 针对 WeChatDeveloper 也有一准备了帮助资料可供参考。
>* 看云文档地址：http://www.kancloud.cn/zoujingli/wechat-developer
>* Think.Admin：https://github.com/zoujingli/Think.Admin


Code Repositorie
--
 WeChatDeveloper 为开源项目，允许把它用于任何地方，不受任何约束，欢迎 fork 项目。
>* GitHub 托管地址：https://github.com/zoujingli/WeChatDeveloper
>* OSChina 托管地址：http://git.oschina.net/zoujingli/WeChatDeveloper


Encapsulation
--
* 接入验证 （初级权限）
* 自动回复（文本、图片、语音、视频、音乐、图文） （初级权限）
* 菜单操作（查询、创建、删除） （菜单权限）
* 客服消息（文本、图片、语音、视频、音乐、图文） （认证权限）
* 二维码（创建临时、永久二维码，获取二维码URL） （服务号、认证权限）
* 长链接转短链接接口 （服务号、认证权限）
* 标签操作（查询、创建、修改、移动用户到标签） （认证权限）
* 网页授权（基本授权，用户信息授权） （服务号、认证权限）
* 用户信息（查询用户基本信息、获取关注者列表） （认证权限）
* 多客服功能（客服管理、获取客服记录、客服会话管理） （认证权限）
* 媒体文件（上传、获取） （认证权限）
* 高级群发 （认证权限）
* 模板消息（设置所属行业、添加模板、发送模板消息） （服务号、认证权限）
* 卡券管理（创建、修改、删除、发放、门店管理等） （认证权限）
* 语义理解 （服务号、认证权限）
* 获取微信服务器IP列表 （初级权限）
* 微信JSAPI授权(获取ticket、获取签名) （初级权限）
* 数据统计(用户、图文、消息、接口分析数据) （认证权限）
* 微信支付（网页支付、扫码支付、交易退款、给粉丝打款）（认证服务号并开通支付功能）



Permission Notes
--
* 初级权限：基本权限，任何正常的公众号都有此权限
* 菜单权限：正常的服务号、认证后的订阅号拥有此权限
* 认证权限：分为订阅号、服务号认证，如前缀服务号则仅认证的服务号有此权限
* 支付权限：仅认证后的服务号可以申请此权限


Copyright
--
* WeChatDeveloper 基于`MIT`协议发布，任何人可以用在任何地方，不受约束
* WeChatDeveloper 部分代码来自互联网，若有异议，可以联系作者进行删除


Sponsor Me
--
![赞助](http://zoujingli.oschina.io/static/pay.png)


