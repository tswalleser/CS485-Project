async function load_counties(state_element_id, county_element_id) {
    console.log("Attempting to load counties for " + state_element_id);
    try {
        //Figure out what State is selected to pass through to the fetch so the php can query the database for the counties, then populate form with counties

        const state_element = document.getElementById(state_element_id);
        const county_element = document.getElementById(county_element_id);
        
        county_element.replaceChildren(); //To create new list of values, the children will die

        let state = state_element.value;

        if (!state) {
            throw new Error("State Not Selected")
        }

        const params = new URLSearchParams();
        params.append("state", state);


        const response = await fetch(`/includes/get_counties.php?${params}`);
        
        if (!response.ok) {
        throw new Error(`HTTP Status - ${response.status}`);
        }
        
        const data = await response.json();

        data.forEach(function(element){
            let county_id = element['county_id'];
            let county_name = element['County_Name'];

            let new_option = document.createElement('option');
            new_option.value = county_id;
            new_option.innerText = county_name;

            county_element.appendChild(new_option);
        });
    } catch(error) {
        console.log("Fetch Error: " + error);
    }
}

function match_county(county_element_id, county_id) {
    const county_element = document.getElementById(county_element_id);

    for (element of county_element.children) {
        if (element.value == county_id) {
            element.setAttribute("selected", "true");
            break;
        }
    }
}
