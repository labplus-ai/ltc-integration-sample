FROM node:24-alpine

WORKDIR /app
ENV NG_CLI_ANALYTICS=false

# Install dependencies first (cached unless package files change).
COPY package*.json ./
RUN npm ci

COPY . .

EXPOSE 4200
# --host: reachable from outside the container, --poll: notices file changes on mounted folders.
CMD ["npx", "ng", "serve", "--host", "0.0.0.0", "--poll", "2000"]
