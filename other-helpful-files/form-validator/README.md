# Form Validation Script Builder

The project is for building validation scripts for various form of the simple-membership plugin.


## Prerequisites

Make sure you have the following installed on your system:

- Node.js - Download and install [NodeJS](https://nodejs.org/) and add the path to the environment variables. The LTS version is recommended.
- npm - Installs automatically with nodejs.

## Getting Started

1. **Navigate to the working directory:**
   
   Use the 'cd' command and navigate to the directory where package.json is located.
   ```
   cd other-helpful-files/form-validator
   ```
2. **Install the dependencies:**

   Use the following npm command to install all the dependencies. This will install all the required dependencies for this project to the 'node_modules' folder.
   ```
   npm install
   ```
3. **Compile and bundle for production:**

   To bundle and deploy the developed file, use any of the 'build' script listed in the package.json file's script section. For example to build the javascript file of the registration form, use the following command:
   ```
   npm run build:reg
   ```
4. **Compile and Hot-Reload for Development:**

   Use the following command to run the build command in the watch mode:
   ```
   npm run dev:reg
   ```

## NPM Commands
All the available build commands are listed below:

   ```
   - npm run build:reg     : Builds the registration form validation script once.
   - npm run dev:reg       : Builds the registration form validation script in watch mode.
   - npm run build:profile : Builds the edit profile form validation script once.
   - npm run dev:profile   : Builds the edit profile form validation script in watch mode.
   ```

## Configuration Files
This project uses the [Vite](https://vitejs.dev) and the bundler and build tool. The bundler takes the source files form the 'src' folder and dumps the output according to the specified configuration that is defined in the dedicated vite config files. See all the available configuration options [Vite Configuration Reference](https://vitejs.dev/config/).

```
- vite.config.reg-form.js     : Config files for the registration form.
- vite.config.profile-form.js : Config files for the profile edit form.
```

## Libraries and Packages
All the validation scripts are build on top the [Zod](https://zod.dev/), which is a typeScript-first schema validation with static typed library.


    Need to add more description here
      