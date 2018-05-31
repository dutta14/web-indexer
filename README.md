# web-indexer

The web indexer project is an USC CS 572 project. The purpose of this project is to create a search engine.

This project has four parts.

## link-extractor

The **link extractor** is a Java project that crawls through all the web pages and creates a list of edges, where the two vertices of the edge correspond to the current page and the page being pointed at by a hyperlink. 

Therefore, a page with `n` links contributes `n` edges to the edge-list.

The web pages are parsed using [JSoup](https://jsoup.org/).

## page-rank

The **page rank** is a Python script that uses the output edge-list of the *link extractor* to assign a page rank to each of these pages using [NetworkX](https://networkx.github.io/) library.

The output is a list of pages with their page ranks.

## tika-parser

The **Tika parser** module uses the Apache [Tika](https://tika.apache.org/1.1/parser.html) parser, and parses all the HTML files to create a text file that serves as a dictionary. This will be used by the spell checker in the search engine in the next stage.

It outputs a text file to be used by the `SpellCorrector.php` script.

## website

The website is created using PHP and AngularJS.

### Back-end
The back-end consists of two components - the search component and suggest component. The search component is responsible for retrieving the top 10 results on clicking search. The suggest component is responsible for suggesting the top 10 possible keywords when the user starts typing the keywords.

These results are returned in JSON format.

### Front-end
Using AJAX and AngularJS, the results are displayed on the front-end. The user has a choice to select between Lucene and Pagerank, which switches the algorithm used to retrieve the results.
