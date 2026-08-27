# 测试

## 契约路径

公开行为从唯一调用链断言：

```text
对应通道 *Client::mk(Config) -> call(Request) -> Response 解析方法
                                      |
                                      v
                             Runtime(记录型传输适配器)
```

测试不连接真实微信或支付宝账号。`RecordingTransport` 记录最终 PSR-7 请求并返回夹具响应；只有签名、URI、请求头和实际请求体等报文行为在 HTTP 传输接口观察。

## 覆盖要求

- 新请求形态覆盖成功、输入拒绝和平台错误。
- Token、签名或信任变更覆盖缓存、未知密钥 ID、缺失签名和验签失败。
- 下载覆盖 JSON 错误、字节上限、摘要失败及复制前目标流不被写入。
- 文档示例必须能被 PHP tokenizer 解析，本地 Markdown 链接必须有效。
- 每个公开接口方法必须具有职责、单位、所有权或失败语义所需的 PHPDoc；`docs/api.md` 必须覆盖 `Request` 与 `Response` 的全部非内部公开方法。
- 异常 `context` 断言不包含业务请求体或凭证。

## 命令

```bash
composer cs:fix
composer cs:check
composer analyse
composer validate --strict
composer audit --locked
composer test
git diff --check
```

CI 覆盖 PHP 8.1、8.2、8.3、8.4，并在 PHP 8.1 执行最低依赖验证。测试密钥只存在于测试夹具。
