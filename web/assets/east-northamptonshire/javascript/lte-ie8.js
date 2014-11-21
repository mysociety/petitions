// Let's get that CSS3 working in IE, shall we?
function yieldedAddClass(selector, className, yieldTime) {
    setTimeout(function() {
        $(selector).addClass(className);
    }, yieldTime);
}
function addClassesToElements(elementsClassesArray, yieldTime) {
    $.each(elementsClassesArray, function(index, selectorClassKVP) {
        yieldedAddClass(selectorClassKVP.selector, selectorClassKVP.className, yieldTime);
    });
};
$(document).ready(function() {
        
    // Make magic happen, with very little bloat :)
    addClassesToElements([
        {
            selector: ".mobile-menu-list li:first-child, .main-navigation-list > li:first-child, .quick-links li:first-child",
            className: "first-child"
        },
        {
            selector: "p:last-child, .mobile-menu-list li:last-child, .mobile-menu-list li:last-child, .relevant-list li:last-child, .new-widget:last-child",
            className: "last-child"
        },
        {
            selector: ".main-navigation-list > li:nth-child(2)",
            className: "nth-chd-2"
        },
        {
            selector: ".main-navigation-list > li:nth-child(3)",
            className: "nth-chd-3"
        },
        {
            selector: ".main-navigation-list > li:nth-child(4)",
            className: "nth-chd-4"
        },
        {
            selector: ".main-navigation-list > li:nth-child(5)",
            className: "nth-chd-5"
        },
        {
            selector: ".main-navigation-list > li:nth-child(6)",
            className: "nth-chd-6"
        },
        {
            selector: ".main-navigation-list > li:nth-child(7)",
            className: "nth-chd-7"
        },
        {
            selector: ".sub-sub-navigation:nth-child(4n+1)",
            className: "nth-chd-4np1"
        },
        {
            selector: ".supplement.half:nth-child(odd)",
            className: "nth-chd-odd"
        },
        {
            selector: ".supplement.half:nth-child(even)",
            className: "nth-chd-evn"
        }
    ], 10);

});