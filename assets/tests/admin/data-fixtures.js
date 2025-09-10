const tokenAdminJson = {
  token: "1",
  refresh_token: "2",
  tenants: [
    {
      tenantKey: "ABC",
      title: "ABC Tenant",
      description: "Description",
      roles: ["ROLE_ADMIN"],
    },
  ],
  user: {
    fullname: "John Doe",
    email: "johndoe@example.com",
  },
};

const tokenTenantsJson = {
  token: "1",
  refresh_token: "2",
  tenants: [
    {
      tenantKey: "ABC",
      title: "ABC Tenant",
      description: "Nulla quam ipsam voluptatem cupiditate.",
      roles: ["ROLE_ADMIN"],
    },
    {
      tenantKey: "DEF",
      title: "DEF Tenant",
      description: "Inventore sed libero et.",
      roles: ["ROLE_ADMIN"],
    },
    {
      tenantKey: "XYZ",
      title: "XYC Tenant",
      description: "Itaque quibusdam tempora velit porro ut velit.",
      roles: ["ROLE_ADMIN"],
    },
  ],
  user: {
    fullname: "John Doe",
    email: "johndoe@example.com",
  },
};

const tokenEditorJson = {
  token: "1",
  refresh_token: "2",
  tenants: [
    {
      tenantKey: "ABC",
      title: "ABC Tenant",
      description: "Description",
      roles: ["ROLE_EDITOR"],
    },
  ],
  user: {
    fullname: "John Doe",
    email: "johndoe@example.com",
  },
};

const feedSourcesJson = {
  "@context": "/contexts/FeedSource",
  "@id": "/v2/feed-sources",
  "@type": "hydra:Collection",
  "hydra:totalItems": 5,
  "hydra:member": [
    {
      "@id": "/v2/feed-sources/01JBBP48CS9CV80XRWRP8CAETJ",
      "@type": "FeedSource",
      title: "test 2",
      description: "test 2",
      outputType: "",
      feedType: "test 2",
      secrets: [],
      feeds: [],
      admin: [],
      supportedFeedOutputType: "test 2",
      modifiedBy: "admin@example.com",
      createdBy: "admin@example.com",
      id: "01JBBP48CS9CV80XRWRP8CAETJ",
      created: "2024-10-29T09:26:25.000Z",
      modified: "2024-10-29T09:26:25.000Z",
    },
    {
      "@id": "/v2/feed-sources/01JB9MSQEH75HC3GG75XCVP2WH",
      "@type": "FeedSource",
      title: "Ny datakilde test 3",
      description: "Ny datakilde test 3",
      outputType: "",
      feedType: "App\\Feed\\RssFeedType",
      secrets: [],
      feeds: [
        "/v2/feeds/01JB9R7EPN9NPW117C22NY31KH",
        "/v2/feeds/01JBBQMF72W2V36TWF6VXFA5Z7",
      ],
      admin: [
        {
          key: "rss-url",
          input: "input",
          name: "url",
          type: "url",
          label: "Kilde",
          helpText: "Her kan du skrive rss kilden",
          formGroupClasses: "col-md-6",
        },
        {
          key: "rss-number-of-entries",
          input: "input",
          name: "numberOfEntries",
          type: "number",
          label: "Antal indgange",
          helpText:
            "Her kan du skrive, hvor mange indgange, der maksimalt skal vises.",
          formGroupClasses: "col-md-6 mb-3",
        },
        {
          key: "rss-entry-duration",
          input: "input",
          name: "entryDuration",
          type: "number",
          label: "Varighed pr. indgang (i sekunder)",
          helpText: "Her skal du skrive varigheden pr. indgang.",
          formGroupClasses: "col-md-6 mb-3",
        },
      ],
      supportedFeedOutputType: "rss",
      modifiedBy: "admin@example.com",
      createdBy: "admin@example.com",
      id: "01JB9MSQEH75HC3GG75XCVP2WH",
      created: "2024-10-28T14:24:43.000Z",
      modified: "2024-10-28T15:23:28.000Z",
    },
    {
      "@id": "/v2/feed-sources/01JB1DH8G4CXKGX5JRTYDHDPSP",
      "@type": "FeedSource",
      title: "Calendar datakilde test",
      description: "test",
      outputType: "",
      feedType: "App\\Feed\\CalendarApiFeedType",
      secrets: [],
      feeds: [],
      admin: [],
      supportedFeedOutputType: "calendar",
      modifiedBy: "",
      createdBy: "",
      id: "01JB1DH8G4CXKGX5JRTYDHDPSP",
      created: "2024-10-25T10:43:50.000Z",
      modified: "2024-10-25T10:43:50.000Z",
    },
    {
      "@id": "/v2/feed-sources/01J711Y2Q01VBJ1Y7A1HZQ0ZN6",
      "@type": "FeedSource",
      title: "feed_source_abc_notified",
      description:
        "Ut magnam veritatis velit ut doloribus id. Consequatur ut ipsum exercitationem aliquam laudantium voluptate voluptates perspiciatis. Id occaecati ea rerum facilis molestias et.",
      outputType: "",
      feedType: "App\\Feed\\RssFeedType",
      secrets: [],
      feeds: ["/v2/feeds/01GJD7S1KR10811MTA176C001R"],
      admin: [
        {
          key: "rss-url",
          input: "input",
          name: "url",
          type: "url",
          label: "Kilde",
          helpText: "Her kan du skrive rss kilden",
          formGroupClasses: "col-md-6",
        },
        {
          key: "rss-number-of-entries",
          input: "input",
          name: "numberOfEntries",
          type: "number",
          label: "Antal indgange",
          helpText:
            "Her kan du skrive, hvor mange indgange, der maksimalt skal vises.",
          formGroupClasses: "col-md-6 mb-3",
        },
        {
          key: "rss-entry-duration",
          input: "input",
          name: "entryDuration",
          type: "number",
          label: "Varighed pr. indgang (i sekunder)",
          helpText: "Her skal du skrive varigheden pr. indgang.",
          formGroupClasses: "col-md-6 mb-3",
        },
      ],
      supportedFeedOutputType: "instagram",
      modifiedBy: "",
      createdBy: "",
      id: "01J711Y2Q01VBJ1Y7A1HZQ0ZN6",
      created: "2024-09-05T12:18:20.000Z",
      modified: "2024-09-17T09:33:12.000Z",
    },
    {
      "@id": "/v2/feed-sources/01J1H8GVVR1CVJ1SQK0JXN1X4Q",
      "@type": "FeedSource",
      title: "feed_source_abc_1",
      description:
        "Totam eos molestias omnis aliquam quia qui voluptas. Non eum nihil ut sunt dolor.",
      outputType: "",
      feedType: "App\\Feed\\RssFeedType",
      secrets: [],
      feeds: ["/v2/feeds/01HD49075G0FNY1FNX12VE17K1"],
      admin: [
        {
          key: "rss-url",
          input: "input",
          name: "url",
          type: "url",
          label: "Kilde",
          helpText: "Her kan du skrive rss kilden",
          formGroupClasses: "col-md-6",
        },
        {
          key: "rss-number-of-entries",
          input: "input",
          name: "numberOfEntries",
          type: "number",
          label: "Antal indgange",
          helpText:
            "Her kan du skrive, hvor mange indgange, der maksimalt skal vises.",
          formGroupClasses: "col-md-6 mb-3",
        },
        {
          key: "rss-entry-duration",
          input: "input",
          name: "entryDuration",
          type: "number",
          label: "Varighed pr. indgang (i sekunder)",
          helpText: "Her skal du skrive varigheden pr. indgang.",
          formGroupClasses: "col-md-6 mb-3",
        },
      ],
      supportedFeedOutputType: "rss",
      modifiedBy: "",
      createdBy: "",
      id: "01J1H8GVVR1CVJ1SQK0JXN1X4Q",
      created: "2024-06-29T05:47:07.000Z",
      modified: "2024-10-21T18:01:25.000Z",
    },
  ],
};

const feedSourcesJson2 = {
  "@context": "/contexts/FeedSource",
  "@id": "/v2/feed-sources",
  "@type": "hydra:Collection",
  "hydra:totalItems": 2,
  "hydra:member": [
    {
      "@id": "/v2/feed-sources/01J711Y2Q01VBJ1Y7A1HZQ0ZN6",
      "@type": "FeedSource",
      title: "feed_source_abc_notified",
      description:
        "Ut magnam veritatis velit ut doloribus id. Consequatur ut ipsum exercitationem aliquam laudantium voluptate voluptates perspiciatis. Id occaecati ea rerum facilis molestias et.",
      outputType: "",
      feedType: "App\\Feed\\RssFeedType",
      secrets: [],
      feeds: ["/v2/feeds/01GJD7S1KR10811MTA176C001R"],
      admin: [
        {
          key: "rss-url",
          input: "input",
          name: "url",
          type: "url",
          label: "Kilde",
          helpText: "Her kan du skrive rss kilden",
          formGroupClasses: "col-md-6",
        },
        {
          key: "rss-number-of-entries",
          input: "input",
          name: "numberOfEntries",
          type: "number",
          label: "Antal indgange",
          helpText:
            "Her kan du skrive, hvor mange indgange, der maksimalt skal vises.",
          formGroupClasses: "col-md-6 mb-3",
        },
        {
          key: "rss-entry-duration",
          input: "input",
          name: "entryDuration",
          type: "number",
          label: "Varighed pr. indgang (i sekunder)",
          helpText: "Her skal du skrive varigheden pr. indgang.",
          formGroupClasses: "col-md-6 mb-3",
        },
      ],
      supportedFeedOutputType: "instagram",
      modifiedBy: "",
      createdBy: "",
      id: "01J711Y2Q01VBJ1Y7A1HZQ0ZN6",
      created: "2024-09-05T12:18:20.000Z",
      modified: "2024-09-17T09:33:12.000Z",
    },
    {
      "@id": "/v2/feed-sources/01J1H8GVVR1CVJ1SQK0JXN1X4Q",
      "@type": "FeedSource",
      title: "feed_source_abc_1",
      description:
        "Totam eos molestias omnis aliquam quia qui voluptas. Non eum nihil ut sunt dolor.",
      outputType: "",
      feedType: "App\\Feed\\RssFeedType",
      secrets: [],
      feeds: ["/v2/feeds/01HD49075G0FNY1FNX12VE17K1"],
      admin: [
        {
          key: "rss-url",
          input: "input",
          name: "url",
          type: "url",
          label: "Kilde",
          helpText: "Her kan du skrive rss kilden",
          formGroupClasses: "col-md-6",
        },
        {
          key: "rss-number-of-entries",
          input: "input",
          name: "numberOfEntries",
          type: "number",
          label: "Antal indgange",
          helpText:
            "Her kan du skrive, hvor mange indgange, der maksimalt skal vises.",
          formGroupClasses: "col-md-6 mb-3",
        },
        {
          key: "rss-entry-duration",
          input: "input",
          name: "entryDuration",
          type: "number",
          label: "Varighed pr. indgang (i sekunder)",
          helpText: "Her skal du skrive varigheden pr. indgang.",
          formGroupClasses: "col-md-6 mb-3",
        },
      ],
      supportedFeedOutputType: "rss",
      modifiedBy: "",
      createdBy: "",
      id: "01J1H8GVVR1CVJ1SQK0JXN1X4Q",
      created: "2024-06-29T05:47:07.000Z",
      modified: "2024-10-21T18:01:25.000Z",
    },
  ],
};

const feedSourceSingleJson = {
  "@id": "/v2/feed-sources/01JBBP48CS9CV80XRWRP8CAETJ",
  "@type": "FeedSource",
  title: "feed_source_abc_notified",
  description:
    "Ut magnam veritatis velit ut doloribus id. Consequatur ut ipsum exercitationem aliquam laudantium voluptate voluptates perspiciatis. Id occaecati ea rerum facilis molestias et.",
  outputType: "",
  feedType: "App\\Feed\\RssFeedType",
  secrets: [],
  feeds: ["/v2/feeds/01GJD7S1KR10811MTA176C001R"],
  supportedFeedOutputType: "instagram",
  modifiedBy: "",
  createdBy: "",
  id: "01J711Y2Q01VBJ1Y7A1HZQ0ZN6",
  created: "2024-09-05T12:18:20.000Z",
  modified: "2024-09-17T09:33:12.000Z",
};

const errorJson = {
  "@context": "/contexts/Error",
  "@type": "hydra:Error",
  "hydra:title": "An error occurred",
  "hydra:description": "An error occurred",
};

const emptyJson = {
  "@id": "/v2/slides",
  "hydra:member": [],
  "hydra:totalItems": 0,
  "hydra:view": {
    "@id": "/v2/slides?itemsPerPage=0\u0026title=",
    "@type": "hydra:PartialCollectionView",
  },
};

const adminConfigJson = {
  rejseplanenApiKey: null,
  touchButtonRegions: false,
  showScreenStatus: true,
  loginMethods: [
    {
      type: "username-password",
      enabled: true,
      provider: "username-password",
      label: "",
    },
  ],
  enhancedPreview: true,
};

const clientConfigJson = {
  loginCheckTimeout: 1000 * 60 * 60,
  refreshTokenTimeout: 1000 * 60 * 60,
  releaseTimestampIntervalTimeout: 1000 * 60 * 60,
  pullStrategyInterval: 1000 * 60 * 60,
  schedulingInterval: 1000 * 60 * 60,
  debug: false,
  logging: false,
};

const slidesJson1 = {
  "@id": "/v2/slides",
  "hydra:member": [
    {
      "@type": "Slide",
      "@id": "/v2/slides/00015Y0ZVC18N407JD07SM0YCF",
      title: "Odio quidem ab dolores dolores.",
      description:
        "Accusamus odio atque numquam sunt asperiores ab. Consequatur similique amet velit sit qui doloremque dicta. Ducimus repellat facere odit quia deserunt id quos.",
      created: "1970-01-15T17:36:43.000Z",
      modified: "2021-12-09T12:01:33.000Z",
      modifiedBy: "",
      createdBy: "",
      templateInfo: {
        "@id": "/v2/templates/000YR9PMQC0GMC1TP90V9N07WX",
        options: [],
      },
      theme: "/v2/themes/01FPFH3WX93S4575W6Q9T8K0Y8",
      onPlaylists: [],
      duration: 107879,
      published: {
        from: null,
        to: null,
      },
      media: [
        "/v2/media/00042YZWK214MP01NA1GF517Q2",
        "/v2/media/00TET3FF6K1Q011N5S12621E4H",
        "/v2/media/01DCA32QJY1BH600BV2H140JDK",
      ],
      content: [],
    },
    {
      "@type": "Slide",
      "@id": "/v2/slides/000E7VDT9E0GEJ0W5X040T1CB1",
      title: "Sed ex quo minus doloremque possimus.",
      description:
        "Ipsum quo ipsam rerum ullam labore fugit ut. Repellendus a iusto dolore veritatis. Aut vero assumenda voluptates tempore doloremque expedita pariatur. Sint ducimus qui ducimus asperiores cum.",
      created: "1970-06-27T00:53:51.000Z",
      modified: "2021-12-09T12:01:33.000Z",
      modifiedBy: "",
      createdBy: "",
      templateInfo: {
        "@id": "/v2/templates/01FGC8EXSE1KCC1PTR0NHB0H3R",
        options: [],
      },
      theme: "",
      onPlaylists: [],
      duration: 80335,
      published: {
        from: "2021-03-19T22:20:54.000Z",
        to: "2021-12-28T06:13:08.000Z",
      },
      media: [
        "/v2/media/009H64MSPN1HEH0DTV2DEV085B",
        "/v2/media/00SC0JP6PV2QYS06R70SS31K68",
      ],
      content: [],
    },
    {
      "@type": "Slide",
      "@id": "/v2/slides/001M5VMMV81A6Q1KN10QY90HKE",
      title: "Maxime numquam ducimus quos non.",
      description:
        "Aut ex id earum unde aut itaque vero id. Sunt praesentium harum vel autem.",
      created: "1971-10-11T12:15:35.000Z",
      modified: "2021-12-09T12:01:33.000Z",
      modifiedBy: "",
      createdBy: "",
      templateInfo: {
        "@id": "/v2/templates/000BGWFMBS15N807E60HP91JCX",
        options: [],
      },
      theme: "",
      onPlaylists: [],
      duration: 21254,
      published: {
        from: null,
        to: null,
      },
      media: ["/v2/media/00YMKGY3FM106Q0SRV077G0KEX"],
      content: [],
    },
    {
      "@type": "Slide",
      "@id": "/v2/slides/001M9W40CC0DQE02DR0PS41J7X",
      title: "Est doloremque culpa et facere.",
      description:
        "Eos voluptatem sint fugiat magni omnis aut ut. Odit quod non rerum dolor. Quis deleniti occaecati perspiciatis et esse dolorum. Impedit sunt dolor dolores.",
      created: "1971-10-13T01:40:56.000Z",
      modified: "2021-12-09T12:01:33.000Z",
      modifiedBy: "",
      createdBy: "",
      templateInfo: {
        "@id": "/v2/templates/000BGWFMBS15N807E60HP91JCX",
        options: [],
      },
      theme: "",
      onPlaylists: [],
      duration: 60870,
      published: {
        from: "2021-02-26T20:12:10.000Z",
        to: "2021-06-08T05:44:41.000Z",
      },
      media: ["/v2/media/00BBYAKF190NMJ0FH118V91VV7"],
      content: [],
    },
    {
      "@type": "Slide",
      "@id": "/v2/slides/001W87XHKC0CX10P4215RV2K9B",
      title: "Occaecati temporibus dolore maxime tenetur.",
      description:
        "Qui rem inventore non labore quam nihil in. Sunt rerum consequatur possimus cupiditate iure quo sit ratione. Et quis mollitia et.",
      created: "1972-01-19T20:34:13.000Z",
      modified: "2021-12-09T12:01:33.000Z",
      modifiedBy: "",
      createdBy: "",
      templateInfo: {
        "@id": "/v2/templates/002BAP34VD1EHG0E4J0D2Y00JW",
        options: [],
      },
      theme: "",
      onPlaylists: [],
      duration: 75535,
      published: {
        from: null,
        to: "1996-02-16T00:54:17.000Z",
      },
      media: ["/v2/media/0027FWF7Y014RG0KW9053S1AX6"],
      content: [],
    },
    {
      "@type": "Slide",
      "@id": "/v2/slides/0021MQ8MWP0MXK1NXB1J5918PM",
      title: "Excepturi sed qui.",
      description:
        "Expedita numquam sunt autem nostrum. Sed eos molestiae earum natus. Rerum consectetur et eius illo qui sunt sapiente. Est dolore veritatis cupiditate occaecati.",
      created: "1972-03-26T20:11:47.000Z",
      modified: "2021-12-09T12:01:33.000Z",
      modifiedBy: "",
      createdBy: "",
      templateInfo: {
        "@id": "/v2/templates/017BG9P0E0103F0TFS17FM016M",
        options: [],
      },
      theme: "",
      onPlaylists: [],
      duration: 92829,
      published: {
        from: "2021-12-29T03:25:23.000Z",
        to: null,
      },
      media: ["/v2/media/001AX5W2S909NW0K5A0NVE0NS6"],
      content: [],
    },
    {
      "@type": "Slide",
      "@id": "/v2/slides/002T3E98DP1KK410PC0F2P037P",
      title: "Voluptate aliquid maxime.",
      description:
        "Veniam labore odit omnis sint. Perferendis amet soluta quo quaerat nihil ex eius error.",
      created: "1973-01-24T19:40:11.000Z",
      modified: "2021-12-09T12:01:33.000Z",
      modifiedBy: "",
      createdBy: "",
      templateInfo: {
        "@id": "/v2/templates/002BAP34VD1EHG0E4J0D2Y00JW",
        options: [],
      },
      theme: "/v2/themes/01FPFH3WX93S4575W6Q9T8K0YN",
      onPlaylists: [],
      duration: 41589,
      published: {
        from: null,
        to: "1989-10-31T03:15:44.000Z",
      },
      media: [
        "/v2/media/00CX9N9EJE10WT1PVM10G51N13",
        "/v2/media/016HWRGVWJ170F1AF2099T13JW",
        "/v2/media/01DCA32QJY1BH600BV2H140JDK",
      ],
      content: [],
    },
    {
      "@type": "Slide",
      "@id": "/v2/slides/00367HAGPF0XZA1SDN1ECH1NZX",
      title: "Non ut nobis reprehenderit pariatur.",
      description:
        "Iste asperiores reprehenderit et mollitia et. Molestias iusto repudiandae a qui accusantium nam vel nesciunt.",
      created: "1973-06-24T12:58:37.000Z",
      modified: "2021-12-09T12:01:33.000Z",
      modifiedBy: "",
      createdBy: "",
      templateInfo: {
        "@id": "/v2/templates/000YR9PMQC0GMC1TP90V9N07WX",
        options: [],
      },
      theme: "",
      onPlaylists: [],
      duration: 106091,
      published: {
        from: "2022-01-11T14:56:13.000Z",
        to: "2022-02-05T09:10:20.000Z",
      },
      media: ["/v2/media/00F1M5J6BY1GS517RP1T1B1306"],
      content: [],
    },
    {
      "@type": "Slide",
      "@id": "/v2/slides/0039BTMNG61RJ606WT1QYN1X2K",
      title: "Iusto aut et dicta neque.",
      description:
        "Nihil esse quisquam aut aliquid velit vitae. Dignissimos sit eos voluptatem corporis qui. Maxime qui eaque magni dolor et. Dolorem est velit qui ratione iure provident architecto.",
      created: "1973-08-02T11:45:30.000Z",
      modified: "2021-12-09T12:01:33.000Z",
      modifiedBy: "",
      createdBy: "",
      templateInfo: {
        "@id": "/v2/templates/016MHSNKCH1PQW1VY615JC19Y3",
        options: [],
      },
      theme: "/v2/themes/01FPFH3WX93S4575W6Q9T8K0YB",
      onPlaylists: [],
      duration: 79809,
      published: {
        from: "2021-08-10T06:26:30.000Z",
        to: "2021-08-12T15:26:21.000Z",
      },
      media: ["/v2/media/011ZBTXPF8123R1BA31CQR18HA"],
      content: [],
    },
    {
      "@type": "Slide",
      "@id": "/v2/slides/00446YF1RP0KZH0WQK1QJM1S4T",
      title: "Inventore non nisi odit voluptatem et.",
      description:
        "Et in eum fugit culpa mollitia sunt. Et cupiditate molestias quia sapiente sint maxime qui. Beatae ad quod sed provident quas expedita exercitationem enim. Pariatur illo nam consequatur.",
      created: "1974-07-02T03:19:57.000Z",
      modified: "2021-12-09T12:01:33.000Z",
      modifiedBy: "",
      createdBy: "",
      templateInfo: {
        "@id": "/v2/templates/017BG9P0E0103F0TFS17FM016M",
        options: [],
      },
      theme: "/v2/themes/01FPFH3WXAX8JMJTQHBV2BYEDM",
      onPlaylists: [],
      duration: 37983,
      published: {
        from: "2022-01-24T16:30:24.000Z",
        to: "2022-02-05T09:19:31.000Z",
      },
      media: [
        "/v2/media/0010X8D6JJ03G50T1J1FCW1XH6",
        "/v2/media/00F1M5J6BY1GS517RP1T1B1306",
        "/v2/media/00KXYB7Z291JXC1SY30G161HQD",
      ],
      content: [],
    },
  ],
  "hydra:totalItems": 60,
};

const mediaListJson = {
  "@context": "/contexts/Media",
  "@id": "/v2/media",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "@type": "Media",
      "@id": "/v2/media/001AG48FJC1NVA1EW20TSN13BP",
      title: "Laudantium aut exercitationem rerum itaque unde.",
      description:
        "Quia ut iusto dolores reiciendis animi. Magnam aut ut officiis quae. Nostrum magni et dolore dignissimos in totam qui et.",
      license: "Attribution-NonCommercial-NoDerivs License",
      created: "1971-06-13T05:21:39+01:00",
      modified: "2021-12-09T12:01:34+01:00",
      modifiedBy: "",
      createdBy: "",
      media: [],
      assets: {
        type: "image/jpeg",
        uri: "https://display.local.itkdev.dk/fixtures/template/images/mountain1.jpeg",
        dimensions: {
          height: 3456,
          width: 5184,
        },
        sha: "5a08dbb7fd3a074ed8659694c09cdb94fdb16cb1",
        size: 8945324,
      },
    },
    {
      "@type": "Media",
      "@id": "/v2/media/001AX5W2S909NW0K5A0NVE0NS6",
      title: "Ut eos illum quod.",
      description:
        "Et id est illum veniam eos quam placeat. Maxime ab aut aut fugit. Occaecati ut ea et occaecati repellendus amet. Quia consequuntur quod vel deserunt maiores.",
      license: "Attribution-NoDerivs License",
      created: "1971-06-18T06:59:58+01:00",
      modified: "2021-12-09T12:01:34+01:00",
      modifiedBy: "",
      createdBy: "",
      media: [],
      assets: {
        type: "image/jpeg",
        uri: "https://display.local.itkdev.dk/fixtures/template/images/mountain2.jpeg",
        dimensions: {
          height: 2592,
          width: 3888,
        },
        sha: "0654506b260c33544d39e5613716ef112ab38c7c",
        size: 4855058,
      },
    },
  ],
  "hydra:totalItems": 100,
};

const onSaveJson = {
  title: "A laudantium aspernatur qui.",
  description: "Description",
  created: "1991-09-10T22:36:56+02:00",
  modified: "2021-12-09T12:01:33+01:00",
  modifiedBy: "",
  createdBy: "",
};

const playlistListJson = {
  "@context": "/contexts/Playlist",
  "@id": "/v2/playlists",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "@type": "Playlist",
      "@id": "/v2/playlists/004ZP1XQ1G1MVZ1T0100YN0MPC",
      title: "Et consequatur voluptatibus dolore ut ut.",
      description:
        "Atque maiores nam in occaecati labore labore inventore quo. Enim nemo totam hic. Ut suscipit id sint sed quia.",
      schedules: [],
      created: "1975-06-08T13:12:49.000Z",
      modified: "2022-01-30T15:42:42.000Z",
      modifiedBy: "",
      createdBy: "",
      slides: "/v2/playlists/004ZP1XQ1G1MVZ1T0100YN0MPC/slides",
      campaignScreens: ["/v2/screens/00TH1ZRMZC141K1DMB1H7J03CS"],
      campaignScreenGroups: [],
      isCampaign: true,
      published: {
        from: "2022-03-23T21:30:21.000Z",
        to: "2022-03-25T12:39:29.000Z",
      },
    },
    {
      "@type": "Playlist",
      "@id": "/v2/playlists/007TM6JDGF1ECH07J10ZHY0S7P",
      title: "Voluptas molestias nemo et.",
      description:
        "Aperiam quam sunt quia qui. Iusto ut deserunt veritatis nobis dolorem. Aliquid quo vel quia.",
      schedules: [],
      created: "1978-07-12T17:43:59.000Z",
      modified: "2022-01-30T15:42:42.000Z",
      modifiedBy: "",
      createdBy: "",
      slides: "/v2/playlists/007TM6JDGF1ECH07J10ZHY0S7P/slides",
      campaignScreens: [],
      campaignScreenGroups: [
        "/v2/screen-groups/00EQWMS5WA1WJE0WD81VF91DFH",
        "/v2/screen-groups/0135RY0QPR1DVF0ZT70YHS1NX3",
        "/v2/screen-groups/0135RY0QPR1DVF0ZT70YHS1NX3",
      ],
      isCampaign: false,
      published: {
        from: "2021-03-21T02:10:37.000Z",
        to: "2021-11-08T12:13:31.000Z",
      },
    },
  ],
  "hydra:totalItems": 10,
};

const playlistSingleJson = {
  "@id": "/v2/playlists/004ZP1XQ1G1MVZ1T0100YN0MPC",
  title: "Et consequatur voluptatibus dolore ut ut.",
  description:
    "Atque maiores nam in occaecati labore labore inventore quo. Enim nemo totam hic. Ut suscipit id sint sed quia.",
  schedules: [],
  created: "1975-06-08T13:12:49.000Z",
  modified: "2022-01-30T15:42:42.000Z",
  modifiedBy: "",
  createdBy: "",
  slides: "/v2/playlists/004ZP1XQ1G1MVZ1T0100YN0MPC/slides",
  campaignScreens: ["/v2/screens/00TH1ZRMZC141K1DMB1H7J03CS"],
  campaignScreenGroups: [],
  isCampaign: true,
  published: {
    from: "2022-03-23T21:30:21.000Z",
    to: "2022-03-25T12:39:29.000Z",
  },
};

const screenGroupsListJson = {
  "@id": "/v2/screen-groups",
  "hydra:member": [
    {
      "@type": "ScreenGroup",
      "@id": "/v2/screen-groups/000RAH746Q1AD8011Z1JNV06N3",
      title: "Cupiditate et quidem autem iusto.",
      description:
        "Eos quibusdam consectetur nisi consequatur voluptas. Unde maxime sunt quidem magnam. Sed ipsa voluptas qui occaecati ea nobis.",
      created: "1970-10-30T08:30:07+01:00",
      modified: "2021-12-09T12:01:33+01:00",
      modifiedBy: "",
      createdBy: "",
    },
    {
      "@type": "ScreenGroup",
      "@id": "/v2/screen-groups/0012G98YZS0VTK0Z2T02AD1DC3",
      title: "Dignissimos nihil non sit laudantium.",
      description:
        "Maxime dicta magnam est voluptas voluptas. Est omnis expedita harum reprehenderit debitis laboriosam ab omnis. Sed temporibus iste voluptatibus ut qui est non voluptatem.",
      created: "1971-03-05T20:43:43+01:00",
      modified: "2021-12-09T12:01:33+01:00",
      modifiedBy: "",
      createdBy: "",
    },
    {
      "@type": "ScreenGroup",
      "@id": "/v2/screen-groups/001EZQXKKR0P7X0A3119Z016SB",
      title: "Aut nam accusantium id aut.",
      description:
        "Et est nisi autem nihil. Blanditiis facere repellat et. Est et architecto modi laboriosam corporis et.",
      created: "1971-08-07T23:56:38+01:00",
      modified: "2021-12-09T12:01:33+01:00",
      modifiedBy: "",
      createdBy: "",
    },
    {
      "@type": "ScreenGroup",
      "@id": "/v2/screen-groups/003J350X2D060H00TE1DW50640",
      title: "Velit rem commodi necessitatibus eos.",
      description:
        "Non sequi sed fugit. Nihil cumque nesciunt hic recusandae rem suscipit sunt. Nostrum voluptatem ut consequatur non illum.",
      created: "1973-11-18T23:15:03+01:00",
      modified: "2021-12-09T12:01:33+01:00",
      modifiedBy: "",
      createdBy: "",
    },
    {
      "@type": "ScreenGroup",
      "@id": "/v2/screen-groups/003Z784JQS1PNS1RX1003N0NCD",
      title: "Quod esse voluptas ut.",
      description:
        "Deleniti velit est quasi commodi alias est minima. Harum iusto odio aperiam consequatur qui est. Vel ut id aperiam nobis fugiat et modi. Est dolores rerum id sed excepturi et.",
      created: "1974-05-01T02:50:31+01:00",
      modified: "2021-12-09T12:01:33+01:00",
      modifiedBy: "",
      createdBy: "",
    },
    {
      "@type": "ScreenGroup",
      "@id": "/v2/screen-groups/009YS5ZYPH1B9T0JE01S290T5Y",
      title: "Tenetur voluptatem quo rerum exercitationem.",
      description:
        "Suscipit provident odit in eius sed voluptatibus. Neque aut corporis aspernatur quo qui. Inventore nam est sed sed maiores odio.",
      created: "1980-11-05T17:57:30+01:00",
      modified: "2021-12-09T12:01:33+01:00",
      modifiedBy: "",
      createdBy: "",
    },
    {
      "@type": "ScreenGroup",
      "@id": "/v2/screen-groups/00C1V2MX2S02N30EXM163A0E6X",
      title: "Distinctio quisquam et totam molestias.",
      description:
        "Ad ipsam architecto eum repellat excepturi. Quos deleniti itaque ut reprehenderit aut rerum autem. Nihil et mollitia voluptatibus quis voluptatem. Ex eaque sint nostrum impedit.",
      created: "1983-02-17T02:51:45+01:00",
      modified: "2021-12-09T12:01:33+01:00",
      modifiedBy: "",
      createdBy: "",
    },
    {
      "@type": "ScreenGroup",
      "@id": "/v2/screen-groups/00DDTCJCDX0H101N480E180K4B",
      title: "Cumque facere nulla reiciendis.",
      description:
        "Veritatis doloremque delectus voluptas numquam dolores nobis. Dignissimos quo facere eum iure.",
      created: "1984-08-16T16:14:03+02:00",
      modified: "2021-12-09T12:01:33+01:00",
      modifiedBy: "",
      createdBy: "",
    },
    {
      "@type": "ScreenGroup",
      "@id": "/v2/screen-groups/00GEPM6JRX0V2P0YST0JHA03CC",
      title: "Ea aspernatur odit rerum.",
      description:
        "Adipisci tenetur placeat perspiciatis assumenda. Voluptas officiis magnam reprehenderit possimus non. Tempore delectus numquam veritatis harum natus.",
      created: "1987-12-03T16:33:04+01:00",
      modified: "2021-12-09T12:01:33+01:00",
      modifiedBy: "",
      createdBy: "",
    },
    {
      "@type": "ScreenGroup",
      "@id": "/v2/screen-groups/00KXGYAJ4A1D5P0EA11SKF0BG8",
      title: "A laudantium aspernatur qui.",
      description:
        "Non fugiat nobis occaecati. Sed ut velit beatae amet ea esse. Quo dolorem commodi magni at. Illum voluptatem neque nobis et ut. Ad rerum tempore vel commodi suscipit corrupti.",
      created: "1991-09-10T22:36:56+02:00",
      modified: "2021-12-09T12:01:33+01:00",
      modifiedBy: "",
      createdBy: "",
    },
  ],
  "hydra:totalItems": 20,
};

const screenGroupsSingleJson = {
  "@id": "/v2/screen-groups/00GEPM6JRX0V2P0YST0JHA03CC",
  title: "Ea aspernatur odit rerum.",
  description:
    "Adipisci tenetur placeat perspiciatis assumenda. Voluptas officiis magnam reprehenderit possimus non. Tempore delectus numquam veritatis harum natus.",
  created: "1987-12-03T16:33:04+01:00",
  modified: "2021-12-09T12:01:33+01:00",
  modifiedBy: "",
  createdBy: "",
};

const screensListJson = {
  "@id": "/v2/screens",
  "hydra:totalItems": 2,
  "hydra:member": [
    {
      "@type": "Screen",
      "@id": "/v2/screens/00APXK73HQ11PM0X3P12EG14DZ",
      title: "Ab eos dolorum minima inventore.",
      description:
        "Non inventore ab vitae. Voluptatem assumenda aliquam sunt nulla sint corrupti et. Nihil consectetur facere cum modi aliquid. Non aut voluptas voluptas laudantium.",
      size: "42",
      created: "1981-09-01T17:22:18+02:00",
      modified: "2021-12-09T12:01:33+01:00",
      modifiedBy: "",
      createdBy: "",
      layout: "/v2/layouts/009S1H8VER00GK086N0M1J16K9",
      location:
        "Natus aut est eveniet deleniti nihil voluptatum. Accusamus similique adipisci at qui molestiae quia nihil eligendi. Delectus repellendus ut asperiores ut debitis.",
      regions: [],
      inScreenGroups: "/v2/screens/00APXK73HQ11PM0X3P12EG14DZ/groups",
      dimensions: {
        width: 1920,
        height: 1200,
      },
    },
    {
      "@type": "Screen",
      "@id": "/v2/screens/00AYESM1AR002E0YKH0JQ70185",
      title: "Accusantium aperiam mollitia consectetur.",
      description:
        "Asperiores id aut temporibus expedita quia rem. Sunt possimus voluptas voluptas exercitationem. Totam odio necessitatibus aut velit. Nisi est voluptates suscipit rerum perspiciatis.",
      size: "55",
      created: "1981-12-04T09:31:11+01:00",
      modified: "2021-12-09T12:01:33+01:00",
      modifiedBy: "",
      createdBy: "",
      layout: "/v2/layouts/009S1H8VER00GK086N0M1J16K9",
      location:
        "Occaecati beatae iure molestias sapiente nihil. Tempore quo quibusdam odit quia.",
      regions: [],
      inScreenGroups: "/v2/screens/00AYESM1AR002E0YKH0JQ70185/groups",
      dimensions: {
        width: 1920,
        height: 1200,
      },
    },
  ],
};

const templatesListJson = {
  "@id": "/v2/templates",
  "hydra:member": [
    {
      "@type": "Template",
      "@id": "/v2/templates/00XZXR5XDH0D1M16K10NYQ0A55",
      title: "Est totam provident sunt.",
      description:
        "Tempora qui minus officia quis consequuntur voluptates. Quasi minima eveniet repudiandae laborum dolor quasi totam qui. Iusto enim inventore molestias amet aut.",
      created: "2002-08-30T14:14:07+02:00",
      modified: "2021-12-09T12:01:33+01:00",
      modifiedBy: "",
      createdBy: "",
      resources: {
        admin:
          "http://www.harber.com/atque-inventore-consequatur-mollitia-ducimus-veritatis-doloribus-ad",
        schema: "http://www.kulas.net/quia-unde-quos-error-modi-saepe",
        component: "http://keebler.com/",
        assets: {
          type: "css",
          url: "https://www.borer.biz/voluptas-blanditiis-et-quo-aut-culpa-reiciendis-dolorum",
        },
        options: {
          fade: true,
        },
        content: {
          text: "Accusantium exercitationem animi qui provident ipsa distinctio.",
        },
      },
    },
    {
      "@type": "Template",
      "@id": "/v2/templates/016MHSNKCH1PQW1VY615JC19Y3",
      title: "Ut exercitationem est quia ad quas.",
      description:
        "Laborum quod ut ducimus suscipit quia nostrum. Saepe ex voluptas aut. Sit numquam vel est sunt. Cupiditate excepturi non saepe in voluptatem vel rem quaerat. Magni aut eaque vel deleniti.",
      created: "2012-01-28T09:17:22+01:00",
      modified: "2021-12-09T12:01:33+01:00",
      modifiedBy: "",
      createdBy: "",
      resources: {
        admin:
          "https://vandervort.com/sapiente-quo-est-rerum-nihil-sint-placeat-ipsa-id.html",
        schema:
          "http://www.gulgowski.com/debitis-voluptatem-earum-sed-totam-aut-impedit-facere",
        component:
          "https://lehner.com/officia-ducimus-ea-beatae-eum-amet-provident-sint.html",
        assets: {
          type: "css",
          url: "http://www.emmerich.net/aliquam-excepturi-id-et-ab-voluptate.html",
        },
        options: {
          fade: true,
        },
        content: {
          text: "Ut qui assumenda ex vel quod dolorem perspiciatis eos quis.",
        },
      },
    },
    {
      "@type": "Template",
      "@id": "/v2/templates/017X81AEJV2GJE0NC51KKK0EK8",
      title: "Delectus magnam repudiandae molestiae et a.",
      description:
        "Consequuntur est ut commodi sed. Fugiat repellat harum assumenda sed illo voluptatem nobis fugit. At vero consequatur ut dignissimos. Et inventore ipsam ullam ullam dolor debitis quo saepe.",
      created: "2013-06-17T03:02:15+02:00",
      modified: "2021-12-09T12:01:33+01:00",
      modifiedBy: "",
      createdBy: "",
      resources: {
        admin: "http://www.ratke.com/libero-provident-nihil-minus-alias",
        schema: "http://murazik.biz/",
        component:
          "http://kuhlman.biz/inventore-iure-tempora-perspiciatis-repudiandae-numquam-veniam-sequi-dolorem.html",
        assets: {
          type: "css",
          url: "http://dibbert.biz/eius-et-non-autem",
        },
        options: {
          fade: true,
        },
        content: {
          text: "Praesentium at porro aut corporis quis in quia asperiores sed sit.",
        },
      },
    },
  ],
  "hydra:totalItems": 12,
};

const slidesListJson = {
  "@id": "/v2/slides",
  "hydra:member": [
    {
      "@id": "/v2/slides/0086TQQC671WHA1S150MMF1Q3T",
      title: "Adipisci vero quia.",
      description:
        "Dolores porro ex sed consectetur dolorem aspernatur. Recusandae voluptatem non a aut. Tenetur optio aut fugit reprehenderit. Non dolorum temporibus possimus iure aut quas.",
      created: "1978-12-11T09:47:36.000Z",
      modified: "2021-12-09T12:01:33.000Z",
      modifiedBy: "",
      createdBy: "",
      templateInfo: {
        "@id": "/v2/templates/00MWCNKC4P0X5C0AT70E741E2V",
        options: [],
      },
      theme: "",
      onPlaylists: ["/v2/playlists/00S7ZQK8Y90R351YES1DJN0RKR"],
      duration: 70592,
      published: {
        from: null,
        to: "1989-08-28T18:14:52.000Z",
      },
      media: ["/v2/media/00W2E6VC0V22QK1WYH0CMP151C"],
    },
    {
      "@id": "/v2/slides/00BEFT32281WT51SNJ0JXA11AV",
      title: "Alias et id consequatur.",
      description:
        "Beatae reiciendis provident eos est totam repudiandae molestiae. Qui itaque officiis quibusdam. A nisi minus dolorum excepturi. Atque molestiae non velit ipsum consequatur nisi.",
      created: "1982-06-21T15:09:47.000Z",
      modified: "2021-12-09T12:01:33.000Z",
      modifiedBy: "",
      createdBy: "",
      templateInfo: {
        "@id": "/v2/templates/00PXXK8ND01SRF18YH1WN004VA",
        options: [],
      },
      theme: "/v2/themes/01FPFH3WX93S4575W6Q9T8K0YB",
      onPlaylists: [],
      duration: 40787,
      published: {
        from: "2021-03-12T18:12:47.000Z",
        to: "2021-05-20T08:42:21.000Z",
      },
      media: [
        "/v2/media/0010X8D6JJ03G50T1J1FCW1XH6",
        "/v2/media/0043AB9VWE08071MNB08Q20JSG",
        "/v2/media/009RGQK5AD19K21CZE11HC1DPX",
        "/v2/media/00HM3QQ61107C41D7M0W2B1CEK",
        "/v2/media/01BTDHQH3Z06N40M9V0TJ41YA8",
      ],
    },
    {
      "@id": "/v2/slides/00BCX948J60YCR1TC009A31MPQ",
      title: "Amet impedit eum quia quo.",
      description:
        "Quis nostrum voluptatum quam aut sint enim. Ut sed occaecati deserunt accusantium. Cupiditate et voluptatum ex quidem. Nobis rerum consequatur fugiat occaecati voluptas et.",
      created: "1982-06-02T00:11:19.000Z",
      modified: "2021-12-09T12:01:33.000Z",
      modifiedBy: "",
      createdBy: "",
      templateInfo: {
        "@id": "/v2/templates/000YR9PMQC0GMC1TP90V9N07WX",
        options: [],
      },
      theme: "",
      onPlaylists: [],
      duration: 4902,
      published: {
        from: null,
        to: null,
      },
      media: [
        "/v2/media/00007SBZ470CJ60C7J06H508R8",
        "/v2/media/004MDCEC451GPJ1DW80D1Z026F",
      ],
    },
    {
      "@id": "/v2/slides/000AGMKXFZ0QWX0V5013R80S3S",
      title: "Aperiam maxime autem.",
      description:
        "Ea laboriosam rerum voluptatem. Quos odit veniam cum. Quia non expedita non facere id eum nesciunt. Fugit dolorum ipsa aspernatur.",
      created: "1970-05-11T17:45:13.000Z",
      modified: "2021-12-09T12:01:33.000Z",
      modifiedBy: "",
      createdBy: "",
      templateInfo: {
        "@id": "/v2/templates/018A0WH44D0X1N176Z16CC0HJP",
        options: [],
      },
      theme: "",
      onPlaylists: [],
      duration: 85133,
      published: {
        from: "2021-08-13T23:56:36.000Z",
        to: null,
      },
      media: [
        "/v2/media/00CGESC8E10CZA11EV2AYR1KWC",
        "/v2/media/00HM3QQ61107C41D7M0W2B1CEK",
        "/v2/media/01DCA32QJY1BH600BV2H140JDK",
      ],
    },
    {
      "@id": "/v2/slides/014FR06WMH05WP17B71Q6Z1K7R",
      title: "Architecto dolores facilis et.",
      description:
        "Aspernatur doloremque et perferendis dolorum hic. Ab vel praesentium non pariatur. Omnis ut magni voluptas et. Ducimus harum sed beatae labore.",
      created: "2009-09-25T07:04:00.000Z",
      modified: "2021-12-09T12:01:33.000Z",
      modifiedBy: "",
      createdBy: "",
      templateInfo: {
        "@id": "/v2/templates/00HH42EEHC05QT14VQ041R1KEY",
        options: [],
      },
      theme: "",
      onPlaylists: ["/v2/playlists/00S7ZQK8Y90R351YES1DJN0RKR"],
      duration: 11240,
      published: {
        from: "2020-12-24T06:17:03.000Z",
        to: null,
      },
      media: ["/v2/media/000NDA6BBM1A071RNN0SP40XNJ"],
    },
    {
      "@id": "/v2/slides/00J96KHGSC1HPH0VHW1FSX0CVD",
      title: "Assumenda quaerat sint nihil perspiciatis.",
      description:
        "Eaque quaerat voluptas vitae rerum numquam dolore explicabo. Quas error voluptates quo est vel. Odit cum illum qui aut iure ipsum officiis.",
      created: "1989-11-29T16:39:50.000Z",
      modified: "2021-12-09T12:01:33.000Z",
      modifiedBy: "",
      createdBy: "",
      templateInfo: {
        "@id": "/v2/templates/001R8FR6VC10G51B200TK60QP3",
        options: [],
      },
      theme: "/v2/themes/01FPFH3WX93S4575W6Q9T8K0YN",
      onPlaylists: [],
      duration: 81995,
      published: {
        from: "2021-11-26T14:53:28.000Z",
        to: null,
      },
      media: [
        "/v2/media/009H64MSPN1HEH0DTV2DEV085B",
        "/v2/media/00H1A2WZ1A06BK0W781ETS1EXZ",
        "/v2/media/01BBCV60M903YC07RP09VN1JBZ",
      ],
    },
    {
      "@id": "/v2/slides/00FTMVQRPS18QD0MBY0RQ30FP0",
      title: "Atque nulla explicabo beatae.",
      description:
        "Repudiandae sapiente voluptatibus dolores voluptatem. Recusandae quia iste voluptates tempora fuga aliquam aut hic.",
      created: "1987-03-29T10:52:22.000Z",
      modified: "2021-12-09T12:01:33.000Z",
      modifiedBy: "",
      createdBy: "",
      templateInfo: {
        "@id": "/v2/templates/01F8MJT0AV24D602FN131008YY",
        options: [],
      },
      theme: "/v2/themes/01FPFH3WX93S4575W6Q9T8K0Y9",
      onPlaylists: [],
      duration: 32646,
      published: {
        from: "2021-09-04T10:08:04.000Z",
        to: "2021-10-27T01:46:23.000Z",
      },
      media: [
        "/v2/media/000NDA6BBM1A071RNN0SP40XNJ",
        "/v2/media/003M4E1AQG052J0HPM1X670P4F",
        "/v2/media/009RF9AGF80QP60ECM0CY01A1Y",
        "/v2/media/00QWNXTD5X0VSF1EWQ1H7H0YS2",
      ],
    },
    {
      "@id": "/v2/slides/008J6BKZMA0YHM04S813NF0DZV",
      title: "Atque quis aut qui veritatis.",
      description:
        "Dolore expedita labore praesentium beatae. Labore maiores est voluptates cupiditate praesentium. Maiores est dolorem iusto.",
      created: "1979-05-01T14:59:35.000Z",
      modified: "2021-12-09T12:01:33.000Z",
      modifiedBy: "",
      createdBy: "",
      templateInfo: {
        "@id": "/v2/templates/011763DN4P0FRS1T6B09180KCW",
        options: [],
      },
      theme: "",
      onPlaylists: ["/v2/playlists/01EDTHWKBM0TR3035E0Z631B8E"],
      duration: 86942,
      published: {
        from: "2020-12-26T12:37:10.000Z",
        to: null,
      },
      media: [
        "/v2/media/00JBVT18N31VBA0S3805CN03MC",
        "/v2/media/016SXZGDJR0T321KYJ19B91N1Z",
        "/v2/media/01795VPQZX0GNM00T11JJ71BS8",
      ],
    },
    {
      "@id": "/v2/slides/0118GD1EXJ1CSM1BHW0P7K1KQG",
      title: "Aut odio illum et.",
      description:
        "Voluptatibus dicta dolor suscipit eos. Voluptate atque quia quo blanditiis sint est odio. Voluptatum expedita ipsa ut et placeat laborum. Modi molestias quibusdam sunt ratione nulla sit.",
      created: "2006-03-22T07:17:31.000Z",
      modified: "2021-12-09T12:01:33.000Z",
      modifiedBy: "",
      createdBy: "",
      templateInfo: {
        "@id": "/v2/templates/000AT4BWMM1GJZ17WE1M9M0RFB",
        options: [],
      },
      theme: "/v2/themes/01FPFH3WX93S4575W6Q9T8K0YB",
      onPlaylists: [],
      duration: 104864,
      published: {
        from: "2021-03-27T10:02:38.000Z",
        to: null,
      },
      media: [
        "/v2/media/002B3H5Z2K10M211AK1ERC02PM",
        "/v2/media/00XA10EH8H0YYY1H4B1CC40SSA",
      ],
    },
    {
      "@id": "/v2/slides/0151GSJGTE1JSE032C14JR0CQ2",
      title: "Aut qui placeat consequatur aut.",
      description:
        "Officiis sit adipisci exercitationem ut vero nihil iste voluptas. Temporibus aut saepe rerum sint. Soluta perferendis aliquid voluptatem eos.",
      created: "2010-05-04T04:35:53.000Z",
      modified: "2021-12-09T12:01:33.000Z",
      modifiedBy: "",
      createdBy: "",
      templateInfo: {
        "@id": "/v2/templates/01F8MJT0AV24D602FN131008YY",
        options: [],
      },
      theme: "",
      onPlaylists: [],
      duration: 28141,
      published: {
        from: "2020-12-14T13:20:43.000Z",
        to: "2021-02-11T10:45:45.000Z",
      },
      media: [
        "/v2/media/00KXYB7Z291JXC1SY30G161HQD",
        "/v2/media/01ENQN7X2F0ANK1BNA0J3513JX",
      ],
    },
  ],
  "hydra:totalItems": 100,
};

const themesJson = {
  "@context": "/contexts/Theme",
  "@id": "/v2/themes",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "@type": "Theme",
      "@id": "/v2/themes/01FTNTE788816N6YCW9NVM2JQB",
      title: "Consequatur quisquam recusandae asperiores accusamus.",
      description:
        "Occaecati debitis et saepe eum sint dolorem. Enim ipsum inventore sed libero et velit qui suscipit. Deserunt laudantium quibusdam enim nostrum soluta qui ipsam non.",
      onSlides: [],
      created: "2022-01-30T15:42:42+01:00",
      modified: "2022-01-30T15:42:42+01:00",
      modifiedBy: "",
      createdBy: "",
      css: "",
    },
    {
      "@type": "Theme",
      "@id": "/v2/themes/01FTNTE788816N6YCW9NVM2JQC",
      title: "Sit vitae voluptas sint non.",
      description:
        "Optio quos qui illo error. Laborum vero a officia id corporis. Saepe provident esse hic eligendi. Culpa ut ab voluptas sed a.",
      onSlides: [
        {
          "@type": "Slide",
          "@id": "/v2/slides/007ZR8C9811R741WAP1KHK0T09",
          title: "Mollitia iure culpa exercitationem.",
          description:
            "Facilis nihil minus vel eum. Ut corrupti dicta quo modi temporibus est.",
          created: "1978-09-14T10:51:02+01:00",
          modified: "2022-01-30T15:42:42+01:00",
          modifiedBy: "",
          createdBy: "",
          templateInfo: {
            "@id": "/v2/templates/017BG9P0E0103F0TFS17FM016M",
            options: [],
          },
          theme: "/v2/themes/01FTNTE788816N6YCW9NVM2JQC",
          onPlaylists: [],
          duration: 92789,
          published: {
            from: "2021-08-04T05:24:19+02:00",
            to: "2021-11-14T19:59:53+01:00",
          },
          media: ["/v2/media/00MTYFFTN30XNZ0F350YM31TN5"],
          content: [],
          feed: null,
        },
        {
          "@type": "Slide",
          "@id": "/v2/slides/00GYC65JP01DEG1F6H0R5Q0JBA",
          title: "Voluptatem recusandae hic.",
          description:
            "Nulla occaecati praesentium quia magni ipsum dolor. Et aliquid natus molestiae ut quis. Ad voluptatum qui consequatur deleniti labore est. Voluptas hic veritatis quidem molestias qui.",
          created: "1988-06-15T11:26:36+02:00",
          modified: "2022-01-30T15:42:42+01:00",
          modifiedBy: "",
          createdBy: "",
          templateInfo: {
            "@id": "/v2/templates/002BAP34VD1EHG0E4J0D2Y00JW",
            options: [],
          },
          theme: "/v2/themes/01FTNTE788816N6YCW9NVM2JQC",
          onPlaylists: [],
          duration: 78938,
          published: {
            from: "2021-10-09T12:14:12+02:00",
            to: "2021-12-24T16:18:08+01:00",
          },
          media: [
            "/v2/media/00YB5658GH0TAE1A1N0XBB0YR7",
            "/v2/media/01CVTNA9Y917EX09MY0FNX0GKA",
            "/v2/media/01E4S5SPXR19MP1KGY16TD1XG2",
          ],
          content: [],
          feed: null,
        },
        {
          "@type": "Slide",
          "@id": "/v2/slides/00V28394SD1WBY1BPE1STN13MD",
          title: "Reprehenderit neque nam mollitia quia.",
          description:
            "Omnis aliquam ea architecto dignissimos. Harum provident asperiores neque consequatur sit sed. Quasi ipsa illum et qui deleniti quo.",
          created: "1999-06-23T10:05:00+02:00",
          modified: "2022-01-30T15:42:42+01:00",
          modifiedBy: "",
          createdBy: "",
          templateInfo: {
            "@id": "/v2/templates/01FGC8EXSE1KCC1PTR0NHB0H3R",
            options: [],
          },
          theme: "/v2/themes/01FTNTE788816N6YCW9NVM2JQC",
          onPlaylists: [],
          duration: 69299,
          published: {
            from: "2021-06-09T03:25:34+02:00",
            to: "2021-11-05T02:30:21+01:00",
          },
          media: ["/v2/media/0041NS3DFY1EMS0XGQ025B0425"],
          content: [],
          feed: null,
        },
      ],
      created: "2022-01-30T15:42:42+01:00",
      modified: "2022-01-30T15:42:42+01:00",
      modifiedBy: "",
      createdBy: "",
      css: "",
    },
    {
      "@type": "Theme",
      "@id": "/v2/themes/01FTNTE788816N6YCW9NVM2JQD",
      title: "Enim ex eveniet facere.",
      description:
        "Delectus aut nam et eum. Fugit repellendus illo veritatis. Ex esse veritatis voluptate vel possimus. Aut incidunt sunt cumque asperiores incidunt iure sequi.",
      onSlides: [
        {
          "@type": "Slide",
          "@id": "/v2/slides/003VYYZPPN1MQ61MEM1TE10G2J",
          title: "Quos ducimus culpa consequuntur nulla aliquid.",
          description:
            "At quia quia voluptatibus eius. Delectus quia consequuntur aut nihil. Impedit sit aut dolorum aut dolore. Dolore beatae ipsa voluptas.",
          created: "1974-03-21T14:49:33+01:00",
          modified: "2022-01-30T15:42:42+01:00",
          modifiedBy: "",
          createdBy: "",
          templateInfo: {
            "@id": "/v2/templates/01FGC8EXSE1KCC1PTR0NHB0H3R",
            options: [],
          },
          theme: "/v2/themes/01FTNTE788816N6YCW9NVM2JQD",
          onPlaylists: ["/v2/playlists/00XVQEW1EV0N3K0JZQ0TYS0T1G"],
          duration: 77194,
          published: {
            from: "2021-07-02T08:33:02+02:00",
            to: "2022-03-12T20:52:07+01:00",
          },
          media: [
            "/v2/media/00GEQ02WW10SZ21F9G1MAZ0KR8",
            "/v2/media/00MTYFFTN30XNZ0F350YM31TN5",
            "/v2/media/00SSYSBFHR16PM09MQ0B0K0202",
            "/v2/media/00X6A9GBZM0EHF05AA0B350D8E",
          ],
          content: [],
          feed: null,
        },
        {
          "@type": "Slide",
          "@id": "/v2/slides/00EBKSK8ZZ0Y301VFR0WGQ05F4",
          title: "Maiores repudiandae quibusdam et rerum.",
          description:
            "At totam ut animi nisi ut ut qui. Aspernatur omnis quod temporibus non quo numquam. Dignissimos non eius numquam neque. Numquam modi tempora minus ad aut aut sit.",
          created: "1985-08-21T22:37:57+02:00",
          modified: "2022-01-30T15:42:42+01:00",
          modifiedBy: "",
          createdBy: "",
          templateInfo: {
            "@id": "/v2/templates/0044JYNRTJ1KD0128318R80B3Q",
            options: [],
          },
          theme: "/v2/themes/01FTNTE788816N6YCW9NVM2JQD",
          onPlaylists: [],
          duration: 66516,
          published: {
            from: "2021-08-30T14:57:29+02:00",
            to: "2021-10-24T12:24:48+02:00",
          },
          media: ["/v2/media/00031XCV6Z1W860V5R0SXB0D6R"],
          content: [],
          feed: null,
        },
        {
          "@type": "Slide",
          "@id": "/v2/slides/00VRS9JQ7C1BZZ1NBA1XE50E19",
          title: "Doloremque cum aliquam quis sint.",
          description:
            "Est quos beatae voluptatem optio et sit. Culpa fugiat quam et quisquam error a. Aut molestias quaerat quia aut non ipsum autem. Sunt aspernatur eos dolores quas alias. Culpa aut maiores consectetur.",
          created: "2000-03-29T12:07:31+02:00",
          modified: "2022-01-30T15:42:42+01:00",
          modifiedBy: "",
          createdBy: "",
          templateInfo: {
            "@id": "/v2/templates/01FGC8EXSE1KCC1PTR0NHB0H3R",
            options: [],
          },
          theme: "/v2/themes/01FTNTE788816N6YCW9NVM2JQD",
          onPlaylists: [],
          duration: 28295,
          published: {
            from: "2022-03-18T13:47:21+01:00",
            to: "2022-03-19T12:34:17+01:00",
          },
          media: [
            "/v2/media/003NVKRN4E183T0C431JF7036P",
            "/v2/media/00C07KS3R00PEV24RF09870SH9",
            "/v2/media/0170X462SF1P3205JP1R6K0553",
          ],
          content: [],
          feed: null,
        },
        {
          "@type": "Slide",
          "@id": "/v2/slides/00X2XD9Y011VB31K2T10JW0NR1",
          title: "Eveniet repellendus et autem repellat.",
          description:
            "Rerum praesentium quo sequi. Accusamus fugiat voluptatem est quam. Esse voluptatem quia fugiat nisi delectus omnis.",
          created: "2001-09-04T01:28:51+02:00",
          modified: "2022-01-30T15:42:42+01:00",
          modifiedBy: "",
          createdBy: "",
          templateInfo: {
            "@id": "/v2/templates/017BG9P0E0103F0TFS17FM016M",
            options: [],
          },
          theme: "/v2/themes/01FTNTE788816N6YCW9NVM2JQD",
          onPlaylists: ["/v2/playlists/007TM6JDGF1ECH07J10ZHY0S7P"],
          duration: 99209,
          published: {
            from: "2021-11-11T07:58:12+01:00",
            to: "2021-11-13T19:36:00+01:00",
          },
          media: [
            "/v2/media/00J8PGYF1N12T60T200QN50KKJ",
            "/v2/media/014S7FGP500Z8P18ZW12631TCP",
            "/v2/media/019PTTMBQB0Z2D05VQ0HVC1M5M",
          ],
          content: [],
          feed: null,
        },
        {
          "@type": "Slide",
          "@id": "/v2/slides/011KV2WHQS0NDK1Q1Q01GY0XPS",
          title: "Harum ducimus reiciendis.",
          description:
            "Est aut quis omnis. Cumque id officiis molestias accusamus est molestias. Nulla qui aut quo sunt et.",
          created: "2006-08-10T03:26:54+02:00",
          modified: "2022-01-30T15:42:42+01:00",
          modifiedBy: "",
          createdBy: "",
          templateInfo: {
            "@id": "/v2/templates/01FGC8EXSE1KCC1PTR0NHB0H3R",
            options: [],
          },
          theme: "/v2/themes/01FTNTE788816N6YCW9NVM2JQD",
          onPlaylists: [],
          duration: 7509,
          published: {
            from: "2021-07-22T07:48:05+02:00",
            to: "2021-09-03T23:11:43+02:00",
          },
          media: [
            "/v2/media/008ARWMTYJ0SX810490JQQ0DAK",
            "/v2/media/00E98GAQXH1Y2C1G131MVH0YWZ",
            "/v2/media/00MTYFFTN30XNZ0F350YM31TN5",
            "/v2/media/0142VZYZ7H0XHE1XJB1M730VGS",
            "/v2/media/015H8MEPVH13BE15A11MHJ0KAM",
          ],
          content: [],
          feed: null,
        },
      ],
      created: "2022-01-30T15:42:42+01:00",
      modified: "2022-01-30T15:42:42+01:00",
      modifiedBy: "",
      createdBy: "",
      css: "",
    },
  ],
  "hydra:totalItems": 20,
};

const themesSingleJson = {
  "@type": "Theme",
  "@id": "/v2/themes/01FTNTE788816N6YCW9NVM2JQN",
  title: "Hic minus et omnis porro.",
  description:
    "Odit quia nisi accusantium natus. Ut explicabo corporis eligendi ut. Sapiente ut qui quidem explicabo optio amet velit aut. Iure sed alias asperiores perspiciatis deserunt omnis inventore mollitia.",
  onSlides: [],
  created: "2022-01-30T15:42:42+01:00",
  modified: "2022-01-30T15:42:42+01:00",
  modifiedBy: "",
  createdBy: "",
  css: "",
};

export {
  tokenAdminJson,
  tokenTenantsJson,
  tokenEditorJson,
  feedSourcesJson,
  feedSourcesJson2,
  feedSourceSingleJson,
  errorJson,
  emptyJson,
  slidesJson1,
  clientConfigJson,
  adminConfigJson,
  mediaListJson,
  onSaveJson,
  playlistListJson,
  playlistSingleJson,
  screenGroupsListJson,
  screenGroupsSingleJson,
  screensListJson,
  templatesListJson,
  slidesListJson,
  themesJson,
  themesSingleJson,
};
