# Skill Registry

**Delegator use only.** Any agent that launches sub-agents reads this registry to resolve compact rules, then injects them directly into sub-agent prompts. Sub-agents do NOT read this registry or individual SKILL.md files.

See `_shared/skill-resolver.md` for the full resolution protocol.

## User Skills

| Trigger | Skill | Path |
|---------|-------|------|
| When creating a pull request, opening a PR, or preparing changes for review | branch-pr | C:\Users\informatica\.config\opencode\skills\branch-pr\SKILL.md |
| When writing Go tests, using teatest, or adding test coverage | go-testing | C:\Users\informatica\.config\opencode\skills\go-testing\SKILL.md |
| When creating a GitHub issue, reporting a bug, or requesting a feature | issue-creation | C:\Users\informatica\.config\opencode\skills\issue-creation\SKILL.md |
| When user says "judgment day", "judgment-day", "review adversarial", "dual review", "doble review", "juzgar", "que lo juzguen" | judgment-day | C:\Users\informatica\.config\opencode\skills\judgment-day\SKILL.md |
| When user asks to create a new skill, add agent instructions, or document patterns for AI | skill-creator | C:\Users\informatica\.config\opencode\skills\skill-creator\SKILL.md |

## Compact Rules

Pre-digested rules per skill. Delegators copy matching blocks into sub-agent prompts as `## Project Standards (auto-resolved)`.

### branch-pr
- Every PR MUST link an approved issue (status:approved label)
- Every PR MUST have exactly one `type:*` label
- Branch naming: `^(feat|fix|chore|docs|style|refactor|perf|test|build|ci|revert)\/[a-z0-9._-]+$`
- Conventional commits required: `^(build|chore|ci|docs|feat|fix|perf|refactor|revert|style|test)(\([a-z0-9\._-]+\))?!?: .+`
- PR body must contain: Closes #N, type label checkbox, summary, changes table, test plan, contributor checklist
- Automated checks: issue reference, issue approved status, PR type label, shellcheck

### go-testing
- Use table-driven tests: define `tests []struct{...}` and iterate with `t.Run()`
- Bubbletea TUI: test Model.Update() directly with `tea.KeyMsg`, type assert returned model
- Teatest integration: `teatest.NewTestModel(t, m)`, use `tm.Send()` and `tm.WaitFinished()`, get final model with `tm.FinalModel(t)`
- Golden file testing: compare output with `.golden` files in `testdata/`, use `-update` flag to regenerate
- Test file organization: `*_test.go` alongside source, `testdata/` for golden files, `teatest_test.go` for integration

### issue-creation
- Blank issues disabled — MUST use bug report or feature request template
- Every issue gets `status:needs-review` automatically on creation
- Maintainer MUST add `status:approved` before any PR can be opened
- Questions go to Discussions, NOT issues
- Bug report required fields: pre-flight checks, description, steps, expected/actual behavior, OS, agent, shell
- Feature request required fields: pre-flight checks, problem description, proposed solution, affected area
- Auto-labels: bug/enhancement + status:needs-review

### judgment-day
- Launch TWO blind judges in parallel via `delegate()` — neither knows about the other
- Synthesize: confirmed (both found), suspect (one found), contradictions (disagree)
- Classify WARNINGs: real (normal user can trigger) vs theoretical (contrived scenario)
- Delegate Fix Agent for confirmed CRITICALs and real WARNINGs only
- After fix, re-judge with both judges in parallel
- Convergence: 0 confirmed CRITICALs + 0 confirmed real WARNINGs = APPROVED
- After 2 fix iterations, ASK user before continuing

### skill-creator
- Create skill for: repeated patterns, project-specific conventions, complex workflows
- DON'T create for: trivial patterns, one-off tasks, existing documentation
- Structure: `SKILL.md` (required), `assets/` (templates, schemas), `references/` (local docs)
- Naming: `technology` (generic), `project-component` (project-specific), `action-target` (workflow)
- Frontmatter required: name, description (with trigger), license Apache-2.0, author gentleman-programming, version
- Content: critical patterns first, tables for decisions, minimal examples, no web URLs in references

## Project Conventions

| File | Path | Notes |
|------|------|-------|
| No project convention files found | — | No AGENTS.md, CLAUDE.md, .cursorrules, GEMINI.md, or copilot-instructions.md in project root |

Read the convention files listed above for project-specific patterns and rules. All referenced paths have been extracted — no need to read index files to discover more.
