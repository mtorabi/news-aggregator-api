<?php
return [
    /*
     * Each resource should have:
     *  - name: unique id
     *  - url: API endpoint
     *  - items_path: dot-path to the list of articles in response
     *  - mapping: how to map article fields (title/url/body/date/etc)
     */
    'resources' => [
        'news_api_org' => [
            'name' => 'NewsAPI.Org',
            'url' => function ($from, $to) {
                return 'https://newsapi.org/v2/everything?sortBy=publishedAt&apiKey=' . env('NEWS_API_ORG_KEY') .
                    '&from=' . $from->format('Y-m-d') . '&to=' . $to->format('Y-m-d') . '&language=en&pageSize=100&sources=bbc-news,cnn,the-verge';
            },
            'items_path' => 'articles',
            'mapping' => [
                'external_id' => function ($item) {
                    return base64_encode($item['url']);
                },
                'title' => 'title',
                'url' => 'url',
                'body' => 'description',
                'source' => 'NewsAPI.Org',
                'author' => 'author',
                'image_url' => 'urlToImage',
                'category' => '',
                'published_at' => 'publishedAt',
            ],
        ],
        'ny_times' => [
            'name' => 'NY Times',
            'url' => function ($from, $to) {
                return 'https://api.nytimes.com/svc/search/v2/articlesearch.json?api-key=' . env('NY_TIMES_API_KEY') .
                    '&begin_date=' . $from->format('Ymd') . '&end_date=' . $to->format('Ymd');
            },
            'items_path' => 'response.docs',
            'mapping' => [
                'external_id' => function ($item) {
                    return base64_encode($item['_id']);
                },
                'title' => 'headline.main',
                'url' => 'web_url',
                'body' => 'abstract',
                'source' => 'NY Times',
                'author' => 'byline.original',
                'image_url' => 'multimedia.default.url',
                'category' => 'section_name',
                'published_at' => 'pub_date',
            ],
        ],
        'the_guardian' => [
            'name' => 'The Guardian',
            'url' => function ($from, $to) {
                return 'https://content.guardianapis.com/search?from-date=' . $from->format('Y-m-d') .
                    '&to-date=' . $to->format('Y-m-d') . '&api-key=' . env('GUARDIAN_API_KEY') . '&show-fields=thumbnail,byline,trailText&page-size=100';
            },
            'items_path' => 'response.results',
            'mapping' => [
                'external_id' => function ($item) {
                    return base64_encode($item['id']);
                },
                'title' => 'webTitle',
                'url' => 'webUrl',
                'body' => 'fields.trailText',
                'source' => 'The Guardian',
                'author' => 'fields.byline',
                'image_url' => 'fields.thumbnail',
                'category' => 'sectionName',
                'published_at' => 'webPublicationDate',
            ],
        ]
    ],
];
