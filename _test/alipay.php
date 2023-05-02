<?php

// +----------------------------------------------------------------------
// | WeChatDeveloper
// +----------------------------------------------------------------------
// | 版权所有 2014~2023 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/WeChatDeveloper
// | github 代码仓库：https://github.com/zoujingli/WeChatDeveloper
// +----------------------------------------------------------------------

/**
 * 【 名词解释 】
 * 应用私钥：用来给应用消息进行签名，请务必要妥善保管，避免遗失或泄露。
 * 应用公钥：需要提供给支付宝开放平台，平台会对应用发送的消息进行签名验证。
 * 支付宝公钥：应用收到支付宝发送的同步、异步消息时，使用支付宝公钥验证签名信息。
 * CSR 文件：CSR 即证书签名请求（Certificate Signing Request），CSR 文件（.csr）是申请证书时所需要的一个数据文件。
 * 应用公钥证书：在开放平台上传 CSR 文件后可以获取 CA 机构颁发的应用证书文件（.crt），其中包含了组织/公司名称、应用公钥、证书有效期等内容，一般有效期为 5 年。
 * 支付宝公钥证书：用来验证支付宝消息，包含了支付宝公钥、支付宝公司名称、证书有效期等内容，一般有效期为 5 年。
 * 支付宝根证书：用来验证支付宝消息，包含了根 CA 名称、根 CA 的公钥、证书有效期等内容。
 */

/**
 * 应用公钥证书SN（app_cert_sn）和支付宝根证书SN（alipay_root_cert_sn）的 sn 是指什么？
 * 使用公钥证书签名方式下， 请求参数中需要携带应用公钥证书SN（app_cert_sn）、支付宝根证书SN（alipay_root_cert_sn），这里的SN是指基于开放平台提供的计算规则，动态计算出来的公钥证书序列号，与X.509证书中内置的序列号（serialNumber）不同。
 * 具体的计算规则如下：解析X.509证书文件，获取证书签发机构名称（name）以及证书内置序列号（serialNumber）。 将name与serialNumber拼接成字符串，再对该字符串做MD5计算。
 * 可以参考开放平台SDK源码中的 AlipaySignature.getCertSN 方法实现
 *
 * 不直接使用证书文件中内置的序列号原因：开放平台支持开发者上传自己找第三方权威CA签发的证书，而证书文件中内置序列号只能保证同一家签发机构签发的证书不重复
 */

return [
    // 沙箱模式
    'debug'       => true,
    // 签名类型（RSA|RSA2）
    'sign_type'   => 'RSA2',
    // 应用ID
    'appid'       => '2016090900468879',
    // 支付宝公钥内容 (需1行填写，特别注意：这里不是应用公钥而是支付宝公钥，通常是上传应用公钥换取支付宝公钥，在网页可以复制)
    'public_key'  => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtU71NY53UDGY7JNvLYAhsNa+taTF6KthIHJmGgdio9bkqeJGhHk6ttkTKkLqFgwIfgAkHpdKiOv1uZw6gVGZ7TCu5LfHTqKrCd6Uz+N7hxhY+4IwicLgprcV1flXQLmbkJYzFMZqkXGkSgOsR2yXh4LyQZczgk9N456uuzGtRy7MoB4zQy34PLUkkxR6W1B2ftNbLRGXv6tc7p/cmDcrY6K1bSxnGmfRxFSb8lRfhe0V0UM6pKq2SGGSeovrKHN0OLp+Nn5wcULVnFgATXGCENshRlp96piPEBFwneXs19n+sX1jx60FTR7/rME3sW3AHug0fhZ9mSqW4x401WjdnwIDAQAB',
    // 应用私钥的内容 (需1行填写，特别注意：这里的应用私钥通常由支付宝密钥管理工具生成)
    'private_key' => 'MIIEpAIBAAKCAQEArr8ymPBh/lQ46y76f3VZW4HGV8tClTHJhhlt7BPmn0F8fSc3alKFCiHXIT01ccy3xTPVgcYX26/vvQBt9cJZqxEWapj4hb8BH6lr5BH5MMQ88eyyPLlYicGzt5S4iXUjuImsUguaCO12SoFmM1eaMYKqrELkg3Apbc+16Ktq+puKAohZeozd80NnDc1ossrZtHU1DkUkEJrBeaC7/D+0H/HBHJzc60EMYpvSGgyKzP/ke6xF4ZGilFSKsdoKgS8u7paVMh2SlSO5AsMoELTTlsFS4eYOgD6ZThkBTZZIcyKHM1Wjq6qDfhU3oTkJDYqnLWdtzoqgnUPNQ8nYpYrdeQIDAQABAoIBAQCKWRmH+Bi9MJT3rfPo4VFjnzUW4PfQAuDX6F4coAzgXQpgU6IN7VMjGHOn/zvG4xtDZ6xL2DefWIVnj2V/QuWXCCpFLuLjkLslBA9FO+2b7GGL76eVZ/Bu8AqG95m6SiGDwovJUSIcm1Qh3Jy7XUnYlOjnBPbCERTbuaz9jmleCmOmh4RnpL0DmzvUffNmEuqJfgnF0h2h6CwYBUyGY8G3zpGAyqY+viVPjKA8catcQTqDRywe3ktXxIbi0JqTAYDpb81Ih4NQeHxGMUWHabwij9UTYdPYBgzwVxjcJF4O1cPGdnYeRXm8Neq6L2HbEPRqe/Jiw4i4AB4V3cVWExPhAoGBANWyjP43kguThkFsQZPfDP5xuXIQJ5W24ZcDIwcFYZ5glh+hddHuWsrCf68VHXsf2epeNdqz57xbdtg84FI8N0HmjTCtfuagxXUMPbX4NqqzPqOu2m0T5/b8HCEDV3zWqfMOt+kHR4NytFxLKLwR0a9kypsfZ2z/X4BYXBi6PiZFAoGBANFWw+AL9hJ2q51ePTcZrE788hPepcsdv3OfJjnspykKighTiFgT+m+Kr/AEa9IU/njz/+tcWJuAwOPKDFVd0zVRcxK2Y5WLfo8fYEt6yhq8oa5OKFwInXXVmCPMn0+qduNmZMTzSqL2kaN0U+AH5Te1vlv7I7yuwHDE9vwknBelAoGAbpPN0V3//G2B8yiJZnLszl0akKM7WIUhhnrhDSkDsmhYRlXOGas03+Z1G6vZbXS11kiZpWmiaB0MCii2CteN4FPki2O7XquigUasSBUAdKP7rcc0z2yVg4BBLfQEuVx65IKhN7vEjYg1O+zIT0kJL7EABfTiF8ytJkSSo1j7/+ECgYEA0NPuKHWmLvsE7cKR7IKG2nEIiHvGBl6RmyS7PHNwucdStUWnML4VSOof4p52dKcOx9gYh1Ci79U8FsB7Fzm2tWygD520r/zs7peNNx6xuIROAZTkPBM4CNFfqO66Sf2yBd0iTzqoTPMNi/JCra0So0WBNT7Ngq8NODG0dQmMUSUCgYANC6Y4JIq24nh27jZjTlsVA+jkv5tFK2aKnfRwOYcNhjVNaMyMz5BbvE07zlr/CgViauEMEJorTs+eNY1TEW7ZOOr2lFgbUnT+h/GLCvgwV00nYBEimfIJ96jfIotOLYB74oJJni9wCaJCuurLNfJ0E1XS/8d+1BkFQ+Q7VSDYpg==',
    // 应用公钥的内容（新版资金类接口转 app_cert_sn，如文件 appCertPublicKey_2019051064521003.crt）
    'app_cert'    => '', // 'app_cert_path' => '',
    // 支付宝根证书的内容（新版资金类接口转 alipay_root_cert_sn，如文件 alipayRootCert.crt）
    'root_cert'   => '', // 'root_cert_path' => ''
    // 支付成功通知地址
    'notify_url'  => '',
    // 网页支付回跳地址
    'return_url'  => '',
];