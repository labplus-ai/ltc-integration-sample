FROM node:22-alpine

WORKDIR /app

# Install dependencies first (cached unless package files change).
COPY package*.json ./
RUN npm ci

COPY tsconfig.json ./
COPY src/ ./src/

EXPOSE 3000
CMD ["npm", "start"]
