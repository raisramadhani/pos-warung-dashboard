## GIT Commit Rule

### Commit Message Guidelines

Generate commit messages in Conventional Commits format. Follow this exact structure:

```
<type>[optional scope]: <description>

[optional body]
```

Examples:
- `feat(auth): add new login validation`
- `fix(api): resolve user data fetch timeout`
- `docs: update README installation steps`
- `docs: add JSDoc comments to media component`
- `style: format code according to linting rules`
- `refactor: restructure video platform detection logic`

Types must be one of:
- `feat`: A new feature
- `fix`: A bug fix
- `docs`: Documentation changes including README updates, JSDoc comments, and code documentation
- `style`: Changes not affecting code functionality (formatting, whitespace, etc)
- `refactor`: Code structure changes that neither fix bugs nor add features
- `perf`: Code change that improves performance
- `test`: Adding or correcting tests
- `build`: Changes affecting build system or dependencies
- `ci`: Changes to CI configuration such as GitHub Actions or Jenkins
- `chore`: Other changes not modifying src or test files
- `revert`: Reverting a previous commit

Important: Use `docs` for JSDoc additions/changes, not `refactor`.

For the body:
- Use bullet points (`*`) for multiple items
- Explain WHY the change was needed
- Include relevant context or technical details

Ensure the message is professional and clearly communicates the purpose of the commit.

### Before pushing the commit
 - Before pushing the commit, ensure that you have run all tests and that they pass successfully.
 - Before pushing the commit, make sure to pull the latest changes from the remote repository to avoid any merge conflicts.
 - Before pushing the commit, run linter with `./vendor/bin/pint --parallel`.
