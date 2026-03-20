# Om Industries

## Dependency version compatibility

To prevent npm `ERESOLVE` peer dependency errors for Angular, keep all Angular packages in sync at the same patch version.

- `@angular/core`, `@angular/animations`, `@angular/common`, `@angular/compiler`, `@angular/forms`, `@angular/localize`, `@angular/platform-browser`, `@angular/platform-browser-dynamic`, `@angular/router` should all match (e.g. `18.2.14`).
- In devDependencies, keep `@angular/compiler-cli` and `@angular/language-service` aligned with the same Angular version.

Reinstall after changing versions:

```bash
rm -rf node_modules package-lock.json
npm install
```
