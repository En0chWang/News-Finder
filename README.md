# News-Finder
A Search Engine for News Articles
* Developed a search engine with two search ranking strategies, Lucene and PageRank, to find related news articles.
* Utilized Crawler4j to scrape LA Times website and indexed news webpages using Lucene/Apache Solr.
* Accomplished the autocomplete functionality by applying the FuzzyLookupFactor feature of Lucene/Apache Solr.
* Used Apache Tika as parser and implemented edit distance algorithm to accomplish spelling correction functionality.
* Computed page rank by extracting outgoing links in webpages via jsoup Java library and constructing a directed graph via NetworkX Python package.
