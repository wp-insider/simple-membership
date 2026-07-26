## Compiling TypeScript

This project uses TypeScript for development. Before committing changes to any `.ts` files, transpile them to JavaScript.

### Prerequisites

Install TypeScript globally if it is not already installed:

```bash
npm install -g typescript
```

Alternatively, if TypeScript is installed locally in the project, use:

```bash
npx tsc
```

### Compile the Project

From the project root directory, run:

```bash
tsc
```

This command uses the project's `tsconfig.json` configuration to transpile all TypeScript files.

### Watch for Changes (Optional)

To automatically recompile files whenever they are modified, run:

```bash
tsc --watch
```

### Notes

* Do **not** edit the generated JavaScript files manually. Always make changes in the corresponding TypeScript source files.
* Ensure the generated JavaScript files are updated before creating a commit or pull request.
* If you encounter compilation errors, resolve them before committing your changes.
