<?php

declare(strict_types=1);

namespace We;

use We\Common\Runtime;
use We\Wechat\Common\Internal\AbstractApiClient;
use We\Wechat\Common\Internal\TokenCacheKey;
use We\Wechat\Common\Internal\TokenHttpClient;
use We\Wechat\Common\Internal\WeChatTokenManager;
use We\Wechat\WxAppConfig;

/** 微信小程序通道 Client。 */
final class WxAppClient extends AbstractApiClient
{
    public const NAME = 'wechat.wxapp';

    private function __construct(
        WxAppConfig $config,
        Runtime $runtime,
    ) {
        $transport = $runtime->transport();
        $tokens = new WeChatTokenManager(
            $runtime->cache(),
            new TokenHttpClient($transport, $runtime->spooler()),
            $runtime->cacheKeyPrefix(),
        );
        parent::__construct(
            $transport,
            $runtime->spooler(),
            $config->endpoint->baseUri,
            fn (): string => $tokens->token(
                self::NAME,
                TokenCacheKey::wxAppAccessToken($config->appid, $config->storageScope),
                $config->appid,
                $config->appSecret,
                $config->tokenStrategy,
                $config->endpoint,
            ),
            $runtime->resourcePolicy(),
        );
    }

    /** 使用微信小程序配置创建通道 Client。 */
    public static function mk(WxAppConfig $config, ?Runtime $runtime = null): self
    {
        return new self($config, $runtime ?? new Runtime());
    }

    public function channel(): string
    {
        return self::NAME;
    }
}
