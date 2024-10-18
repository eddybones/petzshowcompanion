export const binding = (source, sourceProperty, target, targetProperty, format) => ({
    source: source,
    sourceProperty: sourceProperty,
    target: target,
    targetProperty: targetProperty,
    format: format
});

const bindFromElement = (binding) => {
    if(binding.format == null) {
        binding.format = (val) => { return val; };
    }

    binding.target[binding.targetProperty] = binding.format(
        binding.source[binding.sourceProperty]
    );
}

const bindFromCheckboxesToArray = (binding) => {
    const checked = binding.source[binding.sourceProperty];
    let values = binding.target[binding.targetProperty];
    if(!Array.isArray(values)) {
        values = [];
    }
    if(checked) {
        values.push(binding.source['value']);
    } else {
        values.splice(values.indexOf(binding.source.value), 1);
    }
    binding.target[binding.targetProperty] = values;
}

const bindToElement = (binding) => {
    if(binding.format == null) {
        binding.format = (val) => { return val; };
    }

    let value = binding.source[binding.sourceProperty];

    Object.defineProperty(binding.source, binding.sourceProperty, {
        get: () => value,
        set: () => binding.target[binding.targetProperty] = binding.format(value),
    });

    binding.target[binding.targetProperty] = binding.format(value);
}

export const OneWayBind = {
    toElement: bindToElement,
    fromElement: bindFromElement,
    fromCheckboxGroup: bindFromCheckboxesToArray,
}