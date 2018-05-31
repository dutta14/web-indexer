var searchApp = angular.module('searchApp', ['ngAnimate', 'ngSanitize']);

searchApp.controller('srchController', function srchController($http, $scope) {
    
    function httpreq(params) {
        $http({
            method: 'GET',
            url: 'search.php',
            params: params
        }).
            then(function success(response) {
            
                $("#resultcontents").show();
                $scope.data = response.data.data;
                $scope.start = response.data.start;
                $scope.end = response.data.end;
                $scope.total = response.data.total;
                $scope.suggested = response.data.suggested;
                console.log($scope.suggested);
            
                if ($scope.suggested === "" || $scope.forcesearch || $scope.suggested === $scope.query) {
                    $("#showingresultsfor").hide();
                } else {
                   $("#showingresultsfor").show();
                }
                    
                $("#showing").show();
            
            }, function error(response) {
                console.log(response.error);
            });
    }
    
    $scope.search = function (forcesearch, didyoumean) {
        var term =  didyoumean ? $scope.suggested.trim() : $('#searchbox').val().trim();
        $scope.query = term.replace(/\s+/g, " ");
        
        if($scope.query.length > 0) {
            $("#resultcontents").hide();
            if (didyoumean) {
                $scope.changeterm();

            }
            var algo = $("input[name='algo']:checked").val(),
                param;

            switch (algo) {
            case "lucene":
                param = {q: $scope.query, algo: "lucene"};
                break;
            case "pr":
                param = {q: $scope.query, algo: "pagerank"};
                break;
            }
            if (forcesearch) {
                $scope.forcesearch = true;
                $("#showingresultsfor").hide();
                $("#didyoumean").show();
            } else {
                $scope.forcesearch = false;
                $("#showingresultsfor").hide();
                $("#didyoumean").hide();
            }
            param['force'] = forcesearch;
            httpreq(param);
        }
    };
    
    $scope.getsuggest = function () {
        var term = $('#searchbox').val().replace(/\s+/g, " ").trim().toLowerCase();
        param = {q: term};
        httpreq_suggest(param);
    };
    
    function httpreq_suggest(params) {
        $http({
            method: 'GET',
            url: 'suggest.php',
            params: params
        }).
            then(function success(response) {
                console.log(response.data);
                $scope.prefix = response.data.prefix;
                var n = params.q.split(" ");
                $scope.suggestions = response.data.suggest.suggest.suggest[n[n.length - 1]].suggestions;
                var arr = [];
                for (var i = 0; i < $scope.suggestions.length; i+=1) {
                    arr.push($scope.prefix + " " + $scope.suggestions[i].term);
                }
                $scope.suggestions = arr;
            
            }, function error(response) {
                console.log(response.error);
            });
    }

    
    $scope.getdesc = function (item) {
        return item.desc != null ? item.desc : "N/A";
    }
    
    $scope.changeterm = function() {
         $("#showingresultsfor").hide();
         $('#searchbox').val($scope.suggested);
    }
    
    $("#showing").hide();
    $("#showingresultsfor").hide();
    $("#didyoumean").hide();
    $("#default_select").prop('checked', true);
    
    $("#searchbox").autocomplete({
        source: function(request, response) {
            response($scope.suggestions);
        }
    });
});