# 问题跟踪：GitHub

本仓库的需求、规格和开发任务统一存放在 GitHub Issues。所有操作使用 `gh` CLI，并由当前工作目录的 Git remote 自动推断仓库。

## 操作约定

- **创建 Issue**：使用 `gh issue create --title "..." --body "..."`；多行正文使用 heredoc 传入。
- **读取 Issue**：使用 `gh issue view <number> --comments`，同时读取正文、评论和标签。
- **查询 Issue**：使用 `gh issue list`，按任务要求设置 `--state`、`--label` 和 JSON 过滤条件。
- **评论 Issue**：使用 `gh issue comment <number> --body "..."`。
- **修改标签**：使用 `gh issue edit <number> --add-label "..."` 或 `--remove-label "..."`。
- **关闭 Issue**：使用 `gh issue close <number> --comment "..."`。

## Pull Request 分流边界

**不把 Pull Request 作为需求分流入口。**

外部 Pull Request 默认不进入 Issue triage 队列。若任务明确指定某个 Pull Request，仍可按该任务要求单独读取和处理。

GitHub 的 Issue 和 Pull Request 共用编号空间。遇到裸编号（例如 `#42`）时，先判断其实际类型，不得仅凭编号假定它是 Issue。

## 技能操作语义

- 当技能要求“发布到问题跟踪器”时，创建 GitHub Issue。
- 当技能要求“读取相关工单”时，读取对应 Issue 及其评论和标签。
- 在 Issue 上进行评论、改标签或关闭等远端写操作前，遵守当前任务的授权边界。
