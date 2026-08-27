<?php

declare(strict_types=1);

namespace We;

use We\Common\Runtime;
use We\Wechat\Common\Internal\AbstractApiClient;
use We\Wechat\Common\Internal\TokenCacheKey;
use We\Wechat\Common\Internal\TokenHttpClient;
use We\Wechat\Common\Internal\WeChatTokenManager;
use We\Wechat\WeChatConfig;

/** 微信公众号通道 Client。 */
final class WeChatClient extends AbstractApiClient
{
    public const NAME = 'wechat.platform';

    private function __construct(
        WeChatConfig $config,
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
                TokenCacheKey::weChatAccessToken($config->appid, $config->storageScope),
                $config->appid,
                $config->appSecret,
                $config->tokenStrategy,
                $config->endpoint,
            ),
            $runtime->resourcePolicy(),
        );
    }

    /** 使用微信公众号配置创建通道 Client。 */
    public static function mk(WeChatConfig $config, ?Runtime $runtime = null): self
    {
        return new self($config, $runtime ?? new Runtime());
    }

    public function channel(): string
    {
        return self::NAME;
    }
}
