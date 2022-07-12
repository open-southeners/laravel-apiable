import { defineUserConfig } from 'vuepress'
import { defaultTheme } from '@vuepress/theme-default'
import { searchPlugin } from '@vuepress/plugin-search'

export default defineUserConfig({
  base: '/laravel-apiable/',
  lang: 'en-US',
  title: 'Laravel Apiable',
  description: 'Integrate JSON:API resources on your Laravel API',

  plugins: [
    searchPlugin({
      maxSuggestions: 10
    }),
  ],

  theme: defaultTheme({
    repo: 'skore/laravel-json-api',
    
    navbar: [
      {
        text: 'Home',
        link: '/README.md',
      },
      {
        text: 'Guide',
        children: [
          {
            text: 'Installation',
            link: '/guide/README.md'
          },
          '/guide/usage.md',
          '/guide/testing.md',
          '/guide/frontend.md',
        ],
      },
      {
        text: 'Comparison',
        link: '/comparison.md',
      }
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Introduction',
          children: [
            {
              text: 'Installation',
              link: '/guide/README.md'
            },
            {
              text: 'Usage',
              link: '/guide/usage.md'
            },
            {
              text: 'Testing',
              link: '/guide/testing.md'
            },
            {
              text: 'Frontend',
              link: '/guide/frontend.md'
            },
          ]
        },
        // {
        //   text: 'Upgrading',
        //   link: '/guide/upgrading.md'
        // },
      ],
    },
  }),
})
