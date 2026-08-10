# 测试与贡献

项目使用 PHPUnit、PHPStan 和 PHP CS Fixer。测试不连接真实微信或支付宝账号，外部平台通过注入的 Guzzle 适配器替换。

## 本地准备

```bash
composer install
composer validate --strict
```

项目要求 PHP 8.1 或更高版本，以及 JSON、OpenSSL、SimpleXML 扩展。

macOS 使用 Homebrew OpenSSL 3 时，如果系统默认配置无法生成 RSA 测试密钥，可显式设置配置文件：

```bash
OPENSSL_CONF=/opt/homebrew/etc/openssl@3/openssl.cnf composer test
```

密钥夹具生成失败会输出 OpenSSL 错误栈和当前 `OPENSSL_CONF`，用于区分环境问题与 SDK 回归。

## 开发循环

对单个行为先运行对应测试文件：

```bash
vendor/bin/phpunit -c phpunit.xml tests/PaymentClientTest.php
composer analyse
```

提交前执行完整质量门禁：

```bash
composer cs:fix
composer cs:check
composer analyse
composer validate --strict
composer test
```

`composer cs:fix` 会修改文件；其余命令应以零退出码完成。

## CI 矩阵

CI 包含：

- Composer 严格元数据校验。
- PHP CS Fixer dry-run。
- PHPStan 静态分析。
- PHP 8.1、8.2、8.3、8.4 完整 PHPUnit 测试。
- PHP 8.1 最低依赖组合测试。

标签发布复用同一 CI 工作流。任一质量任务失败都不会创建 GitHub Release。

## 测试边界

测试优先经过调用方可见接口：

1. 根 `Client` 和六个平台客户端。
2. 配置对象的构造与 `fromArray()`。
3. `StoreCacheInterface` 的可替换语义。
4. 只在外部平台边界替换 Guzzle HTTP 客户端。

不要测试私有消息拼接、内部调用次数或实现细节。支付响应验签测试必须签署原始 body；通知测试必须覆盖有效窗口、过期/未来时间戳、配置窗口和显式关闭时间检查。

文件缓存并发回归是一个明确的低层调度例外：测试只用文件路径和 `flock` 确定旧读与新写的交错顺序，最终行为仍只通过公开 `get()`/`set()` 断言。该调度不构成缓存文件格式的公开契约。

文档测试会解析 README 与 `docs/` 中每个 PHP fenced code，示例必须语法完整，不要在 PHP code block 中使用省略号代替表达式。

## 临时文件

缓存测试只在系统临时目录创建隔离文件。并发回归测试依赖 POSIX `fork` 与 `flock`，不支持这些能力的环境会按测试声明处理，不应改为访问生产缓存。
