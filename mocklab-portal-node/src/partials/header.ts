// Shared page header (and the start of the page body).
import { e } from '../services/helpers.js';

export interface HeaderData {
  pageTitle: string;
  heroEyebrow: string;
  heroTitle: string;
  heroSubtitle: string;
  user?: string; // the logged-in user, if any
}

export function header(data: HeaderData): string {
  return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>${e(data.pageTitle)} | MockLab Patient Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/styles/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="/orders/" aria-label="MockLab">mocklab<sup>+</sup></a>
        ${data.user ? `
            <div class="header-user">
                <span>${e(data.user)}</span>
                <a class="btn btn-outline" href="/logout/">Log out</a>
            </div>` : ''}
    </div>
</header>

<section class="hero">
    <div class="container">
        <div class="eyebrow eyebrow-light">${e(data.heroEyebrow)}</div>
        <h1>${e(data.heroTitle)}</h1>
        ${data.heroSubtitle ? `<p>${e(data.heroSubtitle)}</p>` : ''}
    </div>
</section>

<main class="container main">
`;
}
