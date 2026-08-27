<?php

declare(strict_types=1);

namespace We\Common\Contract;

use We\Common\Exception\SdkException;
use We\Common\Request;
use We\Common\Response;

/** 六个通道 Client 共用的出站调用接口。 */
interface ChannelInterface
{
    /** 返回稳定的平台通道标识。 */
    public function channel(): string;

    /**
     * 同步发送一次请求，并返回已完成当前通道协议校验的响应。
     *
     * @throws SdkException 配置、调用、传输、协议、签名、平台或流处理失败
     */
    public function call(Request $request): Response;
}
