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

return [
    // 沙箱模式
    'debug'       => true,
    // 签名类型（RSA|RSA2）
    'sign_type'   => "RSA2",
    // 应用ID
    'appid'       => '2016090900468879',
    // 应用私钥的内容 (1行填写，特别注意：这里的应用私钥通常由支付宝密钥管理工具生成)
    'private_key' => 'MIIEpAIBAAKCAQEArr8ymPBh/lQ46y76f3VZW4HGV8tClTHJhhlt7BPmn0F8fSc3alKFCiHXIT01ccy3xTPVgcYX26/vvQBt9cJZqxEWapj4hb8BH6lr5BH5MMQ88eyyPLlYicGzt5S4iXUjuImsUguaCO12SoFmM1eaMYKqrELkg3Apbc+16Ktq+puKAohZeozd80NnDc1ossrZtHU1DkUkEJrBeaC7/D+0H/HBHJzc60EMYpvSGgyKzP/ke6xF4ZGilFSKsdoKgS8u7paVMh2SlSO5AsMoELTTlsFS4eYOgD6ZThkBTZZIcyKHM1Wjq6qDfhU3oTkJDYqnLWdtzoqgnUPNQ8nYpYrdeQIDAQABAoIBAQCKWRmH+Bi9MJT3rfPo4VFjnzUW4PfQAuDX6F4coAzgXQpgU6IN7VMjGHOn/zvG4xtDZ6xL2DefWIVnj2V/QuWXCCpFLuLjkLslBA9FO+2b7GGL76eVZ/Bu8AqG95m6SiGDwovJUSIcm1Qh3Jy7XUnYlOjnBPbCERTbuaz9jmleCmOmh4RnpL0DmzvUffNmEuqJfgnF0h2h6CwYBUyGY8G3zpGAyqY+viVPjKA8catcQTqDRywe3ktXxIbi0JqTAYDpb81Ih4NQeHxGMUWHabwij9UTYdPYBgzwVxjcJF4O1cPGdnYeRXm8Neq6L2HbEPRqe/Jiw4i4AB4V3cVWExPhAoGBANWyjP43kguThkFsQZPfDP5xuXIQJ5W24ZcDIwcFYZ5glh+hddHuWsrCf68VHXsf2epeNdqz57xbdtg84FI8N0HmjTCtfuagxXUMPbX4NqqzPqOu2m0T5/b8HCEDV3zWqfMOt+kHR4NytFxLKLwR0a9kypsfZ2z/X4BYXBi6PiZFAoGBANFWw+AL9hJ2q51ePTcZrE788hPepcsdv3OfJjnspykKighTiFgT+m+Kr/AEa9IU/njz/+tcWJuAwOPKDFVd0zVRcxK2Y5WLfo8fYEt6yhq8oa5OKFwInXXVmCPMn0+qduNmZMTzSqL2kaN0U+AH5Te1vlv7I7yuwHDE9vwknBelAoGAbpPN0V3//G2B8yiJZnLszl0akKM7WIUhhnrhDSkDsmhYRlXOGas03+Z1G6vZbXS11kiZpWmiaB0MCii2CteN4FPki2O7XquigUasSBUAdKP7rcc0z2yVg4BBLfQEuVx65IKhN7vEjYg1O+zIT0kJL7EABfTiF8ytJkSSo1j7/+ECgYEA0NPuKHWmLvsE7cKR7IKG2nEIiHvGBl6RmyS7PHNwucdStUWnML4VSOof4p52dKcOx9gYh1Ci79U8FsB7Fzm2tWygD520r/zs7peNNx6xuIROAZTkPBM4CNFfqO66Sf2yBd0iTzqoTPMNi/JCra0So0WBNT7Ngq8NODG0dQmMUSUCgYANC6Y4JIq24nh27jZjTlsVA+jkv5tFK2aKnfRwOYcNhjVNaMyMz5BbvE07zlr/CgViauEMEJorTs+eNY1TEW7ZOOr2lFgbUnT+h/GLCvgwV00nYBEimfIJ96jfIotOLYB74oJJni9wCaJCuurLNfJ0E1XS/8d+1BkFQ+Q7VSDYpg==',
    // 支付宝公钥内容 (1行填写，特别注意：这里不是应用公钥而是支付宝公钥，通常是上传应用公钥换取支付宝公钥，在网页可以复制)
    'public_key'  => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtU71NY53UDGY7JNvLYAhsNa+taTF6KthIHJmGgdio9bkqeJGhHk6ttkTKkLqFgwIfgAkHpdKiOv1uZw6gVGZ7TCu5LfHTqKrCd6Uz+N7hxhY+4IwicLgprcV1flXQLmbkJYzFMZqkXGkSgOsR2yXh4LyQZczgk9N456uuzGtRy7MoB4zQy34PLUkkxR6W1B2ftNbLRGXv6tc7p/cmDcrY6K1bSxnGmfRxFSb8lRfhe0V0UM6pKq2SGGSeovrKHN0OLp+Nn5wcULVnFgATXGCENshRlp96piPEBFwneXs19n+sX1jx60FTR7/rME3sW3AHug0fhZ9mSqW4x401WjdnwIDAQAB',
    // 应用公钥的内容（新版资金类接口转 app_cert_sn）
    'app_cert'    => '',
    // 支付宝根证书内容（新版资金类接口转 alipay_root_cert_sn）
    'root_cert'   => '',
    // 支付成功通知地址
    'notify_url'  => '',
    // 网页支付回跳地址
    'return_url'  => '',
];