# 写作与注释规范

本规范适用于 README、主题文档、ADR、代理规则、异常消息、PHPDoc 和代码注释。项目术语以根目录的 [`CONTEXT.md`](../CONTEXT.md) 为准。

## 语言

- 解释性文本使用简体中文，代码标识、协议字段和产品专名保留官方写法。
- 类型、方法、参数、配置键和命令使用反引号，例如 `WeChatClient::mk()`、`access_token`、`composer test`。
- 缩写统一写作 API、HTTP、HTTPS、JSON、XML、URL、URI、ID、RSA、PEM、TTL、SDK。
- 平台凭证统一称为 Token；按协议角色写作 access Token、component Token、authorizer Token 和 refresh Token，官方字段仍保留 `access_token` 等原名。
- 代码中的 adapter、provider、target、body、stream 和 endpoint 在说明文字中分别写作“适配器”“Provider”“目标”“请求体”“流”和“端点”。query、header、raw、serial/key ID 分别写作“查询参数”“请求头或响应头”“原始字节”“序列号或密钥 ID”。

## 项目术语

- **通道 Client**：六个公开入口类之一，例如 `WeChatClient` 或 `AliPayClient`。
- **平台通道**：稳定的协议标识，例如 `wechat.platform`。
- **请求**与**响应**：分别指 `Request` 和 `Response` 表达的出站交互两端。
- **派生资源**：由可信 `Response` 创建并绑定来源平台通道的 `Resource`。
- **运行依赖**：可通过 `Runtime` 复用的缓存、传输、Provider 和资源策略。

避免使用“万能 Client”“业务客户端”“接口对象”“Result 包装”“原始通道”等未定义或已废弃称呼。

## 文档职责

| 文档 | 唯一职责 |
| --- | --- |
| `README.md` | 项目定位、支持范围、最短可运行示例和导航 |
| `docs/api.md` | 公开类型与方法契约 |
| `docs/configuration.md` | Config 构造参数、数组字段和默认端点 |
| 平台主题文档 | 平台差异、配置示例和安全约束 |
| `docs/design.md` | 模块关系、调用顺序和信任边界 |
| `docs/migration-2.0.md` | 破坏性变化和迁移映射 |
| `CONTEXT.md` | 项目领域词汇，不记录实现 |
| `docs/adr/` | 难以逆转且存在真实权衡的架构决策 |
| `docs/research/` | 带日期和一手来源的平台协议事实 |
| `docs/agents/` | AI 开发流程和仓库操作规则 |
| `CHANGELOG.md` | 按版本记录用户可见变化 |

同一规则只在一个权威文档解释，其他文档使用链接或简短摘要。

## Markdown

- 每份文件只有一个一级标题，标题按层级递进。
- 代码块标明语言；PHP 示例包含 `<?php` 和 `declare(strict_types=1)`。
- 示例使用占位凭证，不包含省略号、真实密钥或无法解析的伪代码。
- 本地链接使用相对路径；外部协议事实优先引用官方文档或官方源码。
- 表格用于稳定映射，步骤使用有序列表，普通说明优先使用短段落。

## PHPDoc 与注释

- 每个公开类、接口和枚举使用一句话说明职责，不复述类名。
- 每个公开接口方法使用 PHPDoc 定义实现责任；存在单位、所有权、空值或失败语义时必须写明。
- 实现类不重复接口已经定义的契约；其他公开方法只在调用约束、所有权、异常或返回语义无法从类型看出时添加 PHPDoc。
- 数组形状、泛型、回调和工具无法推导的类型使用 PHPDoc；原生类型不重复描述。
- 内部实现类型使用 `@internal` 标记，避免被误认为稳定公开接口。
- 注释解释安全顺序、并发原因或非显然约束，不逐行复述代码。
- 修改行为时同步修改相邻 PHPDoc、异常消息和权威文档。

## 完成标准

提交前从当前公开类型和方法反推文档，确认术语、完整命名空间、签名、默认值、配置名和异常名与代码一致，并通过文档示例、Markdown 链接、接口 PHPDoc 完整性和项目质量门禁测试。
