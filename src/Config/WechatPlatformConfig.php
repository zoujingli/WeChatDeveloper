<?php

declare(strict_types=1);
/**
 * This file is part of HyperfAdmin.
 *
 * @Link https://thinkadmin.top
 * @Author Anyon<zoujingli@qq.com>
 */

namespace We\Config;

use We\Contract\ConfigInterface;
use We\Exception\WechatException;
use We\Support\CredentialValidator;

/**
 * 微信公众平台基础配置。
 *
 * appId/appSecret 用于获取微信公众平台接口调用凭据 access_token；Token 与 EncodingAESKey 用于服务器配置、消息签名校验和安全模式消息加解密。
 */
final class WechatPlatformConfig implements ConfigInterface
{
    /**
     * 创建微信公众平台配置并执行必填项校验。
     */
    public function __construct(
        public string $appid,
        public string $appSecret,
        public string $token = '',
        public string $encodingAesKey = '',
        /**
         * 业务维度的缓存分桶：Token 键形如 `wechat:app:{appid}:platform:access_token:scope:{storageScope}`；
         * 空字符串则仅按 appid 分组。多租户下同一 appid 若需隔离（极少见）可传租户/账号标识。
         */
        public string $storageScope = '',
    ) {
        $this->validate();
    }

    /**
     * 从数组创建微信公众平台配置，兼容常见下划线字段名。
     *
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            (string)($data['appid'] ?? ''),
            (string)($data['appsecret'] ?? $data['app_secret'] ?? ''),
            (string)($data['token'] ?? ''),
            (string)($data['encodingaeskey'] ?? $data['encoding_aes_key'] ?? ''),
            (string)($data['storage_scope'] ?? $data['storageScope'] ?? ''),
        );
    }

    /**
     * 校验微信公众平台 appid 与 appSecret。
     */
    public function validate(): void
    {
        foreach ([
            'appid' => $this->appid,
            'appSecret' => $this->appSecret,
        ] as $name => $value) {
            if (trim($value) === '') {
                throw new WechatException($name . ' 不能为空');
            }
        }
        if ($this->token !== '') {
            CredentialValidator::assertWechatToken($this->token);
        }
        if ($this->encodingAesKey !== '') {
            if (trim($this->token) === '') {
                throw new WechatException('token 不能为空');
            }
            CredentialValidator::assertEncodingAesKey($this->encodingAesKey, 'encodingAesKey');
        }
    }
}
