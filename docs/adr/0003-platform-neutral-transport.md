---
status: accepted
---

# 使用平台中立的同步 HTTP 传输

公开扩展契约 `We\Common\Transport\HttpTransportInterface` 接收最终 PSR-7 请求和超时毫秒数，并返回原始 PSR-7 响应。生产使用 Guzzle 适配器，测试使用记录型传输适配器。

## 影响

- HTTP 传输不处理 Token、签名、验签或平台错误。
- 公共请求不接受 Guzzle 选项，重定向固定关闭。
- 请求/响应流使用 PSR-7 `StreamInterface`。
- 需要验签或校验摘要的响应先写入受限临时文件，成功后才返回或复制。
- 普通请求只接受相对目标；派生资源强制使用 HTTPS 和网络策略。
