<?php

declare(strict_types=1);

namespace We\Wechat\Common;

/** 微信 access Token 的官方获取策略。 */
enum WeChatTokenStrategy: string
{
    case Standard = 'standard';
    case Stable = 'stable';
}
