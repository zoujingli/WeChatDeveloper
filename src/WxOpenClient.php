<?php

declare(strict_types=1);

namespace We;

use We\Common\Internal\RequestState;
use We\Common\Runtime;
use We\Wechat\Common\Internal\AbstractApiClient;
use We\Wechat\Common\Internal\TokenHttpClient;
use We\Wechat\WxOpen\Internal\WxOpenTokenManager;
use We\Wechat\WxOpenConfig;

/** 微信开放平台第三方平台及授权方代调用通道 Client。 */
final class WxOpenClient extends AbstractApiClient
{
    public const NAME = 'wechat.service';

    private readonly WxOpenTokenManager $tokens;

    private function __construct(
        WxOpenConfig $config,
        Runtime $runtime,
    ) {
        $transport = $runtime->transport();
        $this->tokens = new WxOpenTokenManager(
            $config,
            $runtime->cache(),
            new TokenHttpClient($transport, $runtime->spooler()),
            $runtime->componentTickets(),
            $runtime->authorizers(),
            $runtime->cacheKeyPrefix(),
        );
        parent::__construct(
            $transport,
            $runtime->spooler(),
            $config->endpoint->baseUri,
            $this->tokens->componentToken(...),
            $runtime->resourcePolicy(),
        );
    }

    /** 使用微信开放平台配置创建通道 Client。 */
    public static function mk(WxOpenConfig $config, ?Runtime $runtime = null): self
    {
        return new self($config, $runtime ?? new Runtime());
    }

    public function channel(): string
    {
        return self::NAME;
    }

    protected function validateIdentity(RequestState $request): void
    {
        if ($request->identity === RequestState::IDENTITY_WECHAT_AUTHORIZER && $request->credentialId !== null) {
            return;
        }
        parent::validateIdentity($request);
    }

    protected function identityToken(RequestState $request): ?string
    {
        if ($request->identity === RequestState::IDENTITY_WECHAT_AUTHORIZER && $request->credentialId !== null) {
            return $this->tokens->authorizerToken($request->credentialId);
        }
        if ($request->identity === RequestState::IDENTITY_DEFAULT) {
            return $this->tokens->componentToken();
        }

        return parent::identityToken($request);
    }
}
