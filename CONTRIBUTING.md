# 贡献指南

感谢参与 WeChatDeveloper。项目只接受平台 API 调用机制，不在 SDK 中复制具体业务端点。

## 开始之前

1. 阅读 [项目边界](docs/design.md)、[公开 API](docs/api.md) 和相关平台主题文档。
2. 需求与缺陷按 [Issue 规则](docs/agents/issue-tracker.md) 记录；安全问题按 [安全策略](SECURITY.md) 私密报告。
3. 修改代码、测试或文档时遵守 [AI 开发流程](docs/agents/development.md) 和 [写作与注释规范](docs/style-guide.md)。

## 本地验证

```bash
composer install
composer cs:fix
composer cs:check
composer analyse
composer validate --strict
composer audit --locked
composer test
git diff --check
```

测试不得连接真实微信或支付宝账号，也不得提交真实凭证。外部平台通过测试用记录型传输适配器替换。

## 变更要求

- 新平台行为通过对应通道 `*Client::mk()->call()` 的公开路径测试。
- 实现与直接回归测试放在同一功能提交。
- 公开入口、配置、异常或安全默认值变化时同步更新权威文档和迁移指南。
- 提交标题和中文正文遵守 [提交规范](docs/agents/development.md#6-组织代码提交)。

Pull Request 应保持单一目标，并说明动机、行为边界、验证结果和兼容性影响。
